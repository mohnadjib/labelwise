<?php
/**
 * Core: settings, statuses and the front-end badge.
 *
 * @package Labelwise
 */

defined( 'ABSPATH' ) || exit;

/**
 * Core class.
 */
final class Labelwise {

	const OPTION        = 'labelwise_settings';
	const META_STATUS   = '_labelwise_status';
	const META_REVIEWER = '_labelwise_reviewer';
	const META_TIME     = '_labelwise_reviewed_at';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'the_content', array( __CLASS__, 'inject_badge' ), 20 );
		add_shortcode( 'labelwise_badge', array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	/**
	 * Allowed statuses and their admin labels.
	 *
	 * @return array<string,string>
	 */
	public static function statuses() {
		return array(
			'none'           => __( 'No label', 'labelwise' ),
			'ai_assisted'    => __( 'AI-assisted', 'labelwise' ),
			'ai_generated'   => __( 'AI-generated', 'labelwise' ),
			'human_reviewed' => __( 'AI-assisted, human-reviewed', 'labelwise' ),
		);
	}

	/**
	 * Default settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'post_types'    => array( 'post', 'page' ),
			'position'      => 'top',
			'style'         => 'light',
			'show_reviewer' => 0,
			'policy_url'    => '',
			'labels'        => array(
				'ai_assisted'    => 'AI-assisted content',
				'ai_generated'   => 'AI-generated content',
				'human_reviewed' => 'AI-assisted, reviewed by a human',
			),
			'gate_enabled'  => 0,
			'chatbot_text'  => 'You are chatting with an AI assistant, not a human.',
		);
	}

	/**
	 * Current settings merged with defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function settings() {
		$defaults = self::defaults();
		$saved    = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		$settings           = wp_parse_args( $saved, $defaults );
		$settings['labels'] = wp_parse_args(
			is_array( $settings['labels'] ) ? $settings['labels'] : array(),
			$defaults['labels']
		);
		return $settings;
	}

	/**
	 * Post types the plugin is active on.
	 *
	 * @return string[]
	 */
	public static function enabled_post_types() {
		$settings = self::settings();
		$types    = is_array( $settings['post_types'] ) ? $settings['post_types'] : array();
		return array_values( array_filter( array_map( 'sanitize_key', $types ), 'post_type_exists' ) );
	}

	/**
	 * Stored status for a post, validated.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function get_status( $post_id ) {
		$status = get_post_meta( $post_id, self::META_STATUS, true );
		return array_key_exists( $status, self::statuses() ) ? $status : 'none';
	}

	/**
	 * Register the badge stylesheet.
	 *
	 * @return void
	 */
	public static function register_assets() {
		wp_register_style( 'labelwise-badge', LABELWISE_URL . 'assets/css/badge.css', array(), LABELWISE_VERSION );
	}

	/**
	 * Build the badge markup for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return string Escaped HTML, empty when no badge applies.
	 */
	public static function render_badge( $post_id ) {
		$status = self::get_status( $post_id );
		if ( 'none' === $status ) {
			return '';
		}

		$settings = self::settings();
		$label    = isset( $settings['labels'][ $status ] ) ? $settings['labels'][ $status ] : '';
		if ( '' === $label ) {
			return '';
		}

		$style = 'dark' === $settings['style'] ? 'dark' : 'light';

		wp_enqueue_style( 'labelwise-badge' );

		$meta = '';
		if ( 'human_reviewed' === $status && ! empty( $settings['show_reviewer'] ) ) {
			$reviewer = get_post_meta( $post_id, self::META_REVIEWER, true );
			$time     = (int) get_post_meta( $post_id, self::META_TIME, true );
			if ( '' !== $reviewer ) {
				$meta = sprintf(
					/* translators: 1: reviewer name, 2: date. */
					esc_html__( 'Reviewed by %1$s on %2$s', 'labelwise' ),
					esc_html( $reviewer ),
					esc_html( $time ? wp_date( get_option( 'date_format' ), $time ) : '' )
				);
			}
		}

		$html  = '<aside class="labelwise-badge labelwise-badge--' . esc_attr( $style ) . ' labelwise-badge--' . esc_attr( $status ) . '" role="note" aria-label="' . esc_attr__( 'AI disclosure', 'labelwise' ) . '">';
		$html .= '<span class="labelwise-badge__mark" aria-hidden="true">[AI]</span>';
		$html .= '<span class="labelwise-badge__text">' . esc_html( $label ) . '</span>';
		if ( '' !== $meta ) {
			$html .= '<span class="labelwise-badge__meta">' . $meta . '</span>';
		}
		if ( ! empty( $settings['policy_url'] ) ) {
			$html .= '<a class="labelwise-badge__link" href="' . esc_url( $settings['policy_url'] ) . '">' . esc_html__( 'Learn more', 'labelwise' ) . '</a>';
		}
		$html .= '</aside>';

		/**
		 * Filter the badge HTML.
		 *
		 * @param string $html    Badge markup.
		 * @param int    $post_id Post ID.
		 * @param string $status  Status key.
		 */
		return (string) apply_filters( 'labelwise_badge_html', $html, $post_id, $status );
	}

	/**
	 * Add the badge to the content of enabled post types.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function inject_badge( $content ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$settings = self::settings();
		if ( 'none' === $settings['position'] ) {
			return $content;
		}

		$post = get_post();
		if ( ! $post || ! in_array( $post->post_type, self::enabled_post_types(), true ) ) {
			return $content;
		}

		$badge = self::render_badge( $post->ID );
		if ( '' === $badge ) {
			return $content;
		}

		return 'bottom' === $settings['position'] ? $content . $badge : $badge . $content;
	}

	/**
	 * Shortcode: [labelwise_badge] or [labelwise_badge id="123"].
	 *
	 * @param array<string,string>|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts    = shortcode_atts( array( 'id' => 0 ), $atts, 'labelwise_badge' );
		$post_id = absint( $atts['id'] );
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}
		if ( ! $post_id || ! is_post_publicly_viewable( $post_id ) ) {
			return '';
		}
		return self::render_badge( $post_id );
	}
}
