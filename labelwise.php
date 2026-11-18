<?php
/**
 * Plugin Name:       Labelwise
 * Description:       Label AI-assisted and AI-generated content, record human review, and show a clear disclosure badge on your posts and pages.
 * Version:           0.1.4
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Labelwise
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       labelwise
 *
 * @package Labelwise
 */

defined( 'ABSPATH' ) || exit;

register_activation_hook(
	__FILE__,
	static function () {
		if ( class_exists( 'Labelwise' ) && false === get_option( Labelwise::OPTION ) ) {
			add_option( Labelwise::OPTION, Labelwise::defaults() );
		}
	}
);

/*
 * Only one copy of Labelwise (free or Pro) can run at a time. If another copy is already
 * loaded, this one stays idle and asks the user to choose which one to keep. It never
 * activates or deactivates any plugin by itself.
 */
if ( defined( 'LABELWISE_VERSION' ) ) {
	add_action(
		'admin_notices',
		static function () {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Another copy of Labelwise is already active, so this one is idle. Please deactivate one of the two copies on the Plugins screen.', 'labelwise' ) . '</p></div>';
		}
	);
	return;
}

define( 'LABELWISE_VERSION', '0.1.4' );
define( 'LABELWISE_FILE', __FILE__ );
define( 'LABELWISE_DIR', plugin_dir_path( __FILE__ ) );
define( 'LABELWISE_URL', plugin_dir_url( __FILE__ ) );

require_once LABELWISE_DIR . 'includes/licensing.php';
require_once LABELWISE_DIR . 'includes/class-labelwise.php';
require_once LABELWISE_DIR . 'includes/class-labelwise-admin.php';

/*
 * Boot the plugin. A closure is used on purpose: a named function declared in this file would
 * be declared even when the early return above is hit, and would then clash with a second copy.
 */
add_action(
	'plugins_loaded',
	static function () {
		Labelwise::init();

		if ( is_admin() ) {
			Labelwise_Admin::init();
		}
	}
);
