<?php
/**
 * Plugin Name:        PBO Tools
 * Plugin URI:
 * Description:        PBO Tools ...
 * Version:            1.0.0
 * Author:             Piotr Boguslawski
 * Author URI:         https://wordpress.org/support/profile/piotr-boguslawski
 * Requires at least:  4.3.1
 * Tested up to:       4.3.1
 *
 * Text Domain: pbo-tools
 * Domain Path: /languages/
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

define( 'PBO_TOOLS', 1 );
define( 'PBO_TOOLS_DIR', plugin_dir_path( __FILE__ ) );

require_once( PBO_TOOLS_DIR . 'user-device.php' );

if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	PBO_User_Device::set_user_device_info( $_POST, true );
}

/**
 *
 */
function pbo_tools_initialize() {
	require_once( PBO_TOOLS_DIR . 'query-ex.php' );
	$SE = new SearchEverything();

	if ( ! function_exists( 'is_woocommerce_activated' ) ) {
		function is_woocommerce_activated() {
			return class_exists( 'woocommerce' ) ? true : false;
		}
	}
	if ( is_woocommerce_activated() ) {
		require_once( PBO_TOOLS_DIR . 'woocommerce/wc-product-ex.php' );
	}

}

add_action( 'init', 'pbo_tools_initialize' );


/**
 * Call a shortcode function by tag name.
 * Borrowed from WooThemes Storefront theme.
 *
 * @since 1.0.0
 *
 * @param string $tag The shortcode whose function to call.
 * @param array $atts The attributes to pass to the shortcode function. Optional.
 * @param array $content The shortcode's content. Default is null (none).
 *
 * @return string|bool False on failure, the result of the shortcode on success.
 */
function execute_shortcode( $tag, array $atts = array(), $content = null ) {
	global $shortcode_tags;

	if ( ! isset( $shortcode_tags[ $tag ] ) ) {
		return false;
	}

	return call_user_func( $shortcode_tags[ $tag ], $atts, $content, $tag );
}


/**
 * Cast object to any class.
 *
 * @since 1.0.0
 *
 * @param $class
 * @param $obj
 *
 * @return mixed|null
 */
function dynamic_cast( $class, $obj ) {
	if ( class_exists( $class ) ) {
		return unserialize( preg_replace( "/^O:[0-9]+:\"[^\"]+\":/i", "O:" . strlen( $class ) . ":\"" . $class . "\":", serialize( $obj ) ) );
	} else {
		return null;
	}
}

