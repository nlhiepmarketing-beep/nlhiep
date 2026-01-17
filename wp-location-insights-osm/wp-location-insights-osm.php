<?php
/**
 * Plugin Name: WP Location Insights (OSM)
 * Description: Location Insights block with OpenStreetMap + Overpass API.
 * Version: 1.1.0
 * Author: OpenAI
 * Text Domain: wp-location-insights-osm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPLI_VERSION', '1.1.0' );
define( 'WPLI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPLI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPLI_OPTION_KEY', 'wpli_settings' );

define( 'WPLI_RATE_LIMIT_MAX', 30 );
define( 'WPLI_RATE_LIMIT_WINDOW', 600 );

require_once WPLI_PLUGIN_DIR . 'includes/admin-settings.php';
require_once WPLI_PLUGIN_DIR . 'includes/rest-endpoints.php';
require_once WPLI_PLUGIN_DIR . 'includes/cpt-locations.php';
require_once WPLI_PLUGIN_DIR . 'public/shortcode.php';

register_activation_hook( __FILE__, 'wpli_activate_plugin' );

/**
 * Plugin activation setup.
 */
function wpli_activate_plugin() {
	$defaults = wpli_get_default_settings();
	$settings = get_option( WPLI_OPTION_KEY );
	if ( ! is_array( $settings ) ) {
		update_option( WPLI_OPTION_KEY, $defaults );
		return;
	}
	update_option( WPLI_OPTION_KEY, wp_parse_args( $settings, $defaults ) );
}

/**
 * Get merged settings.
 *
 * @return array
 */
function wpli_get_settings() {
	$defaults = wpli_get_default_settings();
	$settings = get_option( WPLI_OPTION_KEY, array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}
	return wp_parse_args( $settings, $defaults );
}

/**
 * Default settings.
 *
 * @return array
 */
function wpli_get_default_settings() {
	return array(
		'default_radius'          => 5000,
		'default_zoom'            => 15,
		'default_height'          => 420,
		'default_max_results'     => 20,
		'cache_ttl'               => 60,
		'overpass_endpoint'       => 'https://overpass-api.de/api/interpreter',
		'tile_url'                => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
		'tile_attribution'        => '© OpenStreetMap contributors',
		'tile_url_fallback'       => '',
		'tile_attribution_fallback' => '',
		'speed_kmh'               => 25,
		'rounding'                => 'floor',
		'accent_color'            => '#d32f2f',
		'marker_color'            => '#2aa8a8',
		'load_fontawesome'        => 1,
		'load_leaflet'            => 1,
		'label_school'            => 'Trường học',
		'label_supermarket'       => 'Siêu thị',
		'label_park'              => 'Công viên',
		'label_hospital'          => 'Bệnh viện',
		'label_restaurant'        => 'Nhà hàng',
		'label_my_location'       => 'Vị trí của bạn',
		'icon_school'             => 'fa-solid fa-school',
		'icon_supermarket'        => 'fa-solid fa-cart-shopping',
		'icon_park'               => 'fa-solid fa-tree',
		'icon_hospital'           => 'fa-solid fa-hospital',
		'icon_restaurant'         => 'fa-solid fa-utensils',
		'icon_my_location'        => 'fa-solid fa-location-dot',
		'icon_motorcycle'         => 'fa-solid fa-motorcycle',
	);
}
