<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/sofyansitorus
 * @since             1.0.0
 * @package           Wcsdm
 *
 * @wordpress-plugin
 * Plugin Name:       WooReer (formerly WooCommerce Shipping Distance Matrix)
 * Plugin URI:        https://wooreer.com
 * Description:       WooCommerce shipping rates calculator that allows you to easily offer shipping rates based on the distance that calculated using Google Maps Distance Matrix Service API.
 * Version:           2.0.3
 * Author:            Sofyan Sitorus
 * Author URI:        https://github.com/sofyansitorus
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wcsdm
 * Domain Path:       /languages
 *
 * WC requires at least: 3.0.0
 * WC tested up to: 3.5.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

// Defines plugin named constants.
if ( ! defined( 'WCSDM_FILE' ) ) {
	define( 'WCSDM_FILE', __FILE__ );
}
if ( ! defined( 'WCSDM_PATH' ) ) {
	define( 'WCSDM_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WCSDM_URL' ) ) {
	define( 'WCSDM_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'WCSDM_DEFAULT_LAT' ) ) {
	define( 'WCSDM_DEFAULT_LAT', '-6.178784361374902' );
}
if ( ! defined( 'WCSDM_DEFAULT_LNG' ) ) {
	define( 'WCSDM_DEFAULT_LNG', '106.82303292695315' );
}
if ( ! defined( 'WCSDM_TEST_LAT' ) ) {
	define( 'WCSDM_TEST_LAT', '-6.181472315327319' );
}
if ( ! defined( 'WCSDM_TEST_LNG' ) ) {
	define( 'WCSDM_TEST_LNG', '106.8170462364319' );
}

if ( ! function_exists( 'get_plugin_data' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$plugin_data = get_plugin_data( WCSDM_FILE, false, false );

if ( ! defined( 'WCSDM_VERSION' ) ) {
	$wcsdm_version = isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : '1.0.0';
	define( 'WCSDM_VERSION', $wcsdm_version );
}

if ( ! defined( 'WCSDM_METHOD_ID' ) ) {
	$wcsdm_method_id = isset( $plugin_data['TextDomain'] ) ? $plugin_data['TextDomain'] : 'wcsdm';
	define( 'WCSDM_METHOD_ID', $wcsdm_method_id );
}

if ( ! defined( 'WCSDM_METHOD_TITLE' ) ) {
	$wcsdm_method_title = isset( $plugin_data['Name'] ) ? $plugin_data['Name'] : 'WooCommerce Shipping Distance Matrix';
	define( 'WCSDM_METHOD_TITLE', $wcsdm_method_title );
}

/**
 * Include required core files.
 */
require_once WCSDM_PATH . '/includes/helpers.php';

/**
 * Check if WooCommerce plugin is active
 */
if ( ! wcsdm_is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	return;
}

/**
 * Load plugin textdomain.
 *
 * @since 1.0.0
 */
function wcsdm_load_textdomain() {
	load_plugin_textdomain( 'wcsdm', false, basename( WCSDM_PATH ) . '/languages' );
}
add_action( 'plugins_loaded', 'wcsdm_load_textdomain' );

/**
 * Add plugin action links.
 *
 * Add a link to the settings page on the plugins.php page.
 *
 * @since 1.2.3
 *
 * @param  array $links List of existing plugin action links.
 * @return array         List of modified plugin action links.
 */
function wcsdm_plugin_action_links( $links ) {
	$links = array_merge(
		array(
			'<a href="' . esc_url(
				add_query_arg(
					array(
						'page'           => 'wc-settings',
						'tab'            => 'shipping',
						'zone_id'        => 0,
						'wcsdm_settings' => true,
					), admin_url( 'admin.php' )
				)
			) . '">' . __( 'Settings', 'wcsdm' ) . '</a>',
		),
		$links
	);

	if ( ! wcsdm_is_pro() ) {
		$link_pro = array(
			'<a href="https://wooreer.com/?utm_source=wp-admin&utm_medium=action_links" target="_blank">' . __( 'Get Pro Version', 'wcsdm' ) . '</a>',
		);

		$links = array_merge(
			$links,
			$link_pro
		);
	}

	return $links;
}
add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wcsdm_plugin_action_links' );

/**
 * Load the main class
 *
 * @since    1.0.0
 */
function wcsdm_shipping_init() {
	include plugin_dir_path( __FILE__ ) . 'includes/class-wcsdm.php';
}
add_action( 'woocommerce_shipping_init', 'wcsdm_shipping_init' );

/**
 * Register shipping method
 *
 * @since    1.0.0
 * @param array $methods Existing shipping methods.
 */
function wcsdm_shipping_methods( $methods ) {
	$methods['wcsdm'] = 'Wcsdm';
	return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'wcsdm_shipping_methods' );

/**
 * Enqueue both scripts and styles in the admin area.
 *
 * @since    1.0.0
 * @param    string $hook Current admin page hook.
 */
function wcsdm_enqueue_scripts_backend( $hook ) {
	if ( false !== strpos( $hook, 'wc-settings' ) ) {
		$is_debug = defined( 'WCSDM_DEV' ) && WCSDM_DEV;

		// Enqueue admin styles.
		$css_url = WCSDM_URL . 'assets/css/wcsdm-backend.min.css';
		if ( $is_debug ) {
			$css_url = add_query_arg( array( 't' => time() ), str_replace( '.min', '', $css_url ) );
		}

		wp_enqueue_style(
			'wcsdm-backend', // Give the script a unique ID.
			$css_url, // Define the path to the JS file.
			array(), // Define dependencies.
			WCSDM_VERSION, // Define a version (optional).
			false // Specify whether to put in footer (leave this false).
		);

		// Enqueue admin scripts.
		$js_url = WCSDM_URL . 'assets/js/wcsdm-backend.min.js';
		if ( $is_debug ) {
			$js_url = add_query_arg( array( 't' => time() ), str_replace( '.min', '', $js_url ) );
		}

		wp_enqueue_script(
			'wcsdm-backend', // Give the script a unique ID.
			$js_url, // Define the path to the JS file.
			array( 'jquery' ), // Define dependencies.
			WCSDM_VERSION, // Define a version (optional).
			true // Specify whether to put in footer (leave this true).
		);

		wp_localize_script(
			'wcsdm-backend',
			'wcsdm_params',
			array(
				'showSettings' => isset( $_GET['wcsdm_settings'] ) && is_admin(),
				'methodId'     => WCSDM_METHOD_ID,
				'methodTitle'  => wcsdm_is_pro() ? WCSDM_PRO_METHOD_TITLE : WCSDM_METHOD_TITLE,
				'marker'       => WCSDM_URL . 'assets/img/marker.png',
				'defaultLat'   => WCSDM_DEFAULT_LAT,
				'defaultLng'   => WCSDM_DEFAULT_LNG,
				'testLat'      => WCSDM_TEST_LAT,
				'testLng'      => WCSDM_TEST_LNG,
				'language'     => get_locale(),
				'isPro'        => wcsdm_is_pro(),
				'isDebug'      => $is_debug,
				'i18n'         => wcsdm_i18n(),
			)
		);
	}
}
add_action( 'admin_enqueue_scripts', 'wcsdm_enqueue_scripts_backend' );
