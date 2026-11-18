<?php
/**
 * Admin: settings page, editor meta box and list-table column.
 *
 * @package Labelwise
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin class.
 */
final class Labelwise_Admin {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_box' ) );
		add_action( 'save_post', array( __CLASS__, 'save_meta_box' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'register_columns' ) );
		add_action( 'admin_notices', array( __CLASS__, 'gate_notice' ) );
	}

	/**
	 * Add the top-level menu.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_menu_page(
			__( 'Labelwise', 'labelwise' ),
			__( 'Labelwise', 'labelwise' ),
			'manage_options',
			'labelwise',
			array( __CLASS__, 'render_settings_page' ),
			'dashicons-tag',
			81
		);
	}

	/**
	 * Register the settings option.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'labelwise_group',
			Labelwise::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => Labelwise::defaults(),
			)
		);
	}

	/**
	 * Sanitize submitted settings.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string,mixed>
	 */
	public static function sanitize_settings( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = Labelwise::defaults();
		$current  = Labelwise::settings();
		$out      = array();

		$public_types = array_diff( array_keys( get_post_types( array( 'public' => true ) ) ), array( 'attachment' ) );
		$types        = isset( $input['post_types'] ) && is_array( $input['post_types'] ) ? array_map( 'sanitize_key', $input['post_types'] ) : array();
		$out['post_types'] = array_values( array_intersect( $types, $public_types ) );

		$position        = isset( $input['position'] ) ? sanitize_key( $input['position'] ) : 'top';
		$out['position'] = in_array( $position, array( 'top', 'bottom', 'none' ), true ) ? $position : 'top';

		$style        = isset( $input['style'] ) ? sanitize_key( $input['style'] ) : 'light';
		$out['style'] = in_array( $style, array( 'light', 'dark' ), true ) ? $style : 'light';

		$out['show_reviewer'] = empty( $input['show_reviewer'] ) ? 0 : 1;
		$out['policy_url']    = isset( $input['policy_url'] ) ? esc_url_raw( trim( (string) $input['policy_url'] ) ) : '';

		$out['labels'] = array();
		foreach ( $defaults['labels'] as $key => $default_label ) {
			$value                = isset( $input['labels'][ $key ] ) ? sanitize_text_field( (string) $input['labels'][ $key ] ) : '';
			$out['labels'][ $key ] = '' === $value ? $default_label : $value;
		}

		// Pro-only keys are preserved here and handled by the filter below.
		$out['gate_enabled'] = isset( $current['gate_enabled'] ) ? (int) $current['gate_enabled'] : 0;
		$out['chatbot_text'] = isset( $current['chatbot_text'] ) ? $current['chatbot_text'] : $defaults['chatbot_text'];

		/**
		 * Filter sanitized settings (used by the Pro package).
		 *
		 * @param array $out   Sanitized settings.
		 * @param array $input Raw input.
		 */
		return apply_filters( 'labelwise_sanitize_settings', $out, $input );
	}

	/**
	 * Settings page markup.
	 *
	 * @return void
	 */
	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = Labelwise::settings();
		$option   = Labelwise::OPTION;
		$types    = get_post_types( array( 'public' => true ), 'objects' );
		unset( $types['attachment'] );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Labelwise', 'labelwise' ); ?></h1>
			<p><?php esc_html_e( 'Label AI-assisted content, record human review and show a clear disclosure badge to your readers.', 'labelwise' ); ?></p>

			<h2><?php esc_html_e( 'Overview', 'labelwise' ); ?></h2>
			<table class="widefat striped" style="max-width:520px">
				<tbody>
				<?php foreach ( Labelwise::statuses() as $key => $label ) : ?>
					<?php
					if ( 'none' === $key ) {
						continue;
					}
					?>
					<tr>
						<td><?php echo esc_html( $label ); ?></td>
						<td><?php echo esc_html( number_format_i18n( self::count_by_status( $key ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<form method="post" action="options.php">
				<?php settings_fields( 'labelwise_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable on', 'labelwise' ); ?></th>
						<td>
							<?php foreach ( $types as $type ) : ?>
								<label style="display:block">
									<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[post_types][]" value="<?php echo esc_attr( $type->name ); ?>" <?php checked( in_array( $type->name, (array) $settings['post_types'], true ) ); ?> />
									<?php echo esc_html( $type->labels->name ); ?>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="labelwise-position"><?php esc_html_e( 'Badge position', 'labelwise' ); ?></label></th>
						<td>
							<select id="labelwise-position" name="<?php echo esc_attr( $option ); ?>[position]">
								<option value="top" <?php selected( $settings['position'], 'top' ); ?>><?php esc_html_e( 'Above the content', 'labelwise' ); ?></option>
								<option value="bottom" <?php selected( $settings['position'], 'bottom' ); ?>><?php esc_html_e( 'Below the content', 'labelwise' ); ?></option>
								<option value="none" <?php selected( $settings['position'], 'none' ); ?>><?php esc_html_e( 'Do not insert (use the shortcode)', 'labelwise' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Shortcode:', 'labelwise' ); ?> <code>[labelwise_badge]</code></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="labelwise-style"><?php esc_html_e( 'Badge style', 'labelwise' ); ?></label></th>
						<td>
							<select id="labelwise-style" name="<?php echo esc_attr( $option ); ?>[style]">
								<option value="light" <?php selected( $settings['style'], 'light' ); ?>><?php esc_html_e( 'Light', 'labelwise' ); ?></option>
								<option value="dark" <?php selected( $settings['style'], 'dark' ); ?>><?php esc_html_e( 'Dark', 'labelwise' ); ?></option>
							</select>
						</td>
					</tr>
					<?php foreach ( $settings['labels'] as $key => $text ) : ?>
						<tr>
							<th scope="row"><label for="labelwise-label-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( sprintf( /* translators: %s: status name. */ __( 'Badge text: %s', 'labelwise' ), Labelwise::statuses()[ $key ] ) ); ?></label></th>
							<td><input type="text" class="regular-text" id="labelwise-label-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $option ); ?>[labels][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $text ); ?>" /></td>
						</tr>
					<?php endforeach; ?>
					<tr>
						<th scope="row"><?php esc_html_e( 'Reviewer name', 'labelwise' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[show_reviewer]" value="1" <?php checked( ! empty( $settings['show_reviewer'] ) ); ?> />
								<?php esc_html_e( 'Show the reviewer name and date in the badge', 'labelwise' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="labelwise-policy"><?php esc_html_e( 'AI policy page URL', 'labelwise' ); ?></label></th>
						<td>
							<input type="url" class="regular-text" id="labelwise-policy" name="<?php echo esc_attr( $option ); ?>[policy_url]" value="<?php echo esc_attr( $settings['policy_url'] ); ?>" placeholder="https://" />
							<p class="description"><?php esc_html_e( 'Optional. Adds a “Learn more” link to the badge.', 'labelwise' ); ?></p>
						</td>
					</tr>
					<?php
					/**
					 * Extra settings rows (Pro).
					 *
					 * @param array  $settings Current settings.
					 * @param string $option   Option name.
					 */
					do_action( 'labelwise_settings_fields', $settings, $option );
					?>
				</table>
				<?php submit_button(); ?>
			</form>

			<?php
			$labelwise_fs  = labelwise_fs();
			$has_pro       = $labelwise_fs && $labelwise_fs->can_use_premium_code();
			$upgrade_url   = $labelwise_fs ? $labelwise_fs->get_upgrade_url() : ( defined( 'LABELWISE_UPGRADE_URL' ) ? LABELWISE_UPGRADE_URL : '' );
			if ( ! $has_pro ) :
				?>
				<hr />
				<h2><?php esc_html_e( 'Labelwise Pro', 'labelwise' ); ?></h2>
				<p><?php esc_html_e( 'Publishing gate for unreviewed AI content, review audit log, machine-readable markers (meta tag, REST field, RSS), chatbot notice and an AI-use policy page generator.', 'labelwise' ); ?></p>
				<?php if ( $upgrade_url ) : ?>
					<p><a class="button button-primary" href="<?php echo esc_url( $upgrade_url ); ?>"><?php esc_html_e( 'Upgrade to Pro', 'labelwise' ); ?></a></p>
				<?php endif; ?>
			<?php endif; ?>

			<p class="description" style="margin-top:2em">
				<?php esc_html_e( 'Labelwise helps you document and disclose AI use. It does not provide legal advice and does not guarantee compliance with any law.', 'labelwise' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Count content items with a given status.
	 *
	 * @param string $status Status key.
	 * @return int
	 */
	private static function count_by_status( $status ) {
		$types = Labelwise::enabled_post_types();
		if ( empty( $types ) ) {
			return 0;
		}
		$query = new WP_Query(
			array(
				'post_type'              => $types,
				'post_status'            => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Small admin-only count.
				'meta_query'             => array(
					array(
						'key'   => Labelwise::META_STATUS,
						'value' => $status,
					),
				),
			)
		);
		return (int) $query->found_posts;
	}

	/**
	 * Register the editor meta box.
	 *
	 * @return void
	 */
	public static function register_meta_box() {
		foreach ( Labelwise::enabled_post_types() as $type ) {
			add_meta_box(
				'labelwise_disclosure',
				__( 'AI disclosure', 'labelwise' ),
				array( __CLASS__, 'render_meta_box' ),
				$type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Meta box markup.
	 *
	 * @param WP_Post $post Current post.
	 * @return void
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'labelwise_save_' . $post->ID, 'labelwise_nonce' );
		$status   = Labelwise::get_status( $post->ID );
		$reviewer = (string) get_post_meta( $post->ID, Labelwise::META_REVIEWER, true );
		?>
		<p>
			<label for="labelwise-status"><strong><?php esc_html_e( 'Status', 'labelwise' ); ?></strong></label><br />
			<select id="labelwise-status" name="labelwise_status" style="width:100%">
				<?php foreach ( Labelwise::statuses() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="labelwise-reviewer"><strong><?php esc_html_e( 'Reviewed by', 'labelwise' ); ?></strong></label><br />
			<input type="text" id="labelwise-reviewer" name="labelwise_reviewer" value="<?php echo esc_attr( $reviewer ); ?>" style="width:100%" />
		</p>
		<?php
	}

	/**
	 * Save the meta box.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public static function save_meta_box( $post_id, $post ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['labelwise_nonce'] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['labelwise_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'labelwise_save_' . $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) || ! in_array( $post->post_type, Labelwise::enabled_post_types(), true ) ) {
			return;
		}

		$old = Labelwise::get_status( $post_id );

		$new = isset( $_POST['labelwise_status'] ) ? sanitize_key( wp_unslash( $_POST['labelwise_status'] ) ) : 'none';
		if ( ! array_key_exists( $new, Labelwise::statuses() ) ) {
			$new = 'none';
		}
		/**
		 * Filter the status about to be saved (used by the Pro package).
		 *
		 * @param string $new     Requested status.
		 * @param int    $post_id Post ID.
		 * @param string $old     Previous status.
		 */
		$new = (string) apply_filters( 'labelwise_sanitize_status', $new, $post_id, $old );

		$reviewer = isset( $_POST['labelwise_reviewer'] ) ? sanitize_text_field( wp_unslash( $_POST['labelwise_reviewer'] ) ) : '';
		if ( 'human_reviewed' === $new && '' === $reviewer ) {
			$user     = wp_get_current_user();
			$reviewer = $user->exists() ? $user->display_name : '';
		}

		if ( 'none' === $new ) {
			delete_post_meta( $post_id, Labelwise::META_STATUS );
			delete_post_meta( $post_id, Labelwise::META_REVIEWER );
			delete_post_meta( $post_id, Labelwise::META_TIME );
		} else {
			update_post_meta( $post_id, Labelwise::META_STATUS, $new );
			update_post_meta( $post_id, Labelwise::META_REVIEWER, $reviewer );
			if ( 'human_reviewed' === $new ) {
				if ( 'human_reviewed' !== $old || ! get_post_meta( $post_id, Labelwise::META_TIME, true ) ) {
					update_post_meta( $post_id, Labelwise::META_TIME, time() );
				}
			} else {
				delete_post_meta( $post_id, Labelwise::META_TIME );
			}
		}

		if ( $old !== $new ) {
			/**
			 * Fires when a post's disclosure status changes.
			 *
			 * @param int    $post_id  Post ID.
			 * @param string $old      Previous status.
			 * @param string $new      New status.
			 * @param string $reviewer Reviewer name.
			 */
			do_action( 'labelwise_status_changed', $post_id, $old, $new, $reviewer );
		}
	}

	/**
	 * Add the status column to enabled post types.
	 *
	 * @return void
	 */
	public static function register_columns() {
		foreach ( Labelwise::enabled_post_types() as $type ) {
			add_filter( "manage_{$type}_posts_columns", array( __CLASS__, 'add_column' ) );
			add_action( "manage_{$type}_posts_custom_column", array( __CLASS__, 'render_column' ), 10, 2 );
		}
	}

	/**
	 * Add a column.
	 *
	 * @param array<string,string> $columns Columns.
	 * @return array<string,string>
	 */
	public static function add_column( $columns ) {
		$columns['labelwise'] = __( 'AI label', 'labelwise' );
		return $columns;
	}

	/**
	 * Render the column.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function render_column( $column, $post_id ) {
		if ( 'labelwise' !== $column ) {
			return;
		}
		$status   = Labelwise::get_status( $post_id );
		$statuses = Labelwise::statuses();
		echo 'none' === $status ? '&mdash;' : esc_html( $statuses[ $status ] );
	}

	/**
	 * Show a one-time notice when the Pro gate held a post back.
	 *
	 * @return void
	 */
	public static function gate_notice() {
		$key = 'labelwise_gate_' . get_current_user_id();
		if ( ! get_transient( $key ) ) {
			return;
		}
		delete_transient( $key );
		echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Labelwise: this AI-labelled content needs a human review before it can be published. It was saved as “Pending review”.', 'labelwise' ) . '</p></div>';
	}
}
