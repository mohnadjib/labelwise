<?php
/**
 * Licensing is only used by the Pro package. This build has none.
 *
 * @package Labelwise
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'labelwise_fs' ) ) {
	/**
	 * Always null in the free package.
	 *
	 * @return null
	 */
	function labelwise_fs() {
		return null;
	}
}
