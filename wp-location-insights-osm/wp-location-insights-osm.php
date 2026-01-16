<?php
/**
 * Plugin Name: WP Location Insights (OSM)
 * Description: Location insights block using OpenStreetMap + Overpass API.
 * Version: 1.0.0
 * Author: OpenAI
 * Text Domain: wp-location-insights-osm
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPLI_OSM_VERSION', '1.0.0' );

define( 'WPLI_OSM_PATH', plugin_dir_path( __FILE__ ) );

define( 'WPLI_OSM_URL', plugin_dir_url( __FILE__ ) );

require_once WPLI_OSM_PATH . 'includes/rest-endpoints.php';
require_once WPLI_OSM_PATH . 'includes/admin-settings.php';
require_once WPLI_OSM_PATH . 'includes/locations.php';
require_once WPLI_OSM_PATH . 'public/shortcode.php';

/**
 * Get plugin settings with defaults.
 *
 * @return array
 */
function wpli_osm_get_settings() {
	$defaults = array(
		'default_radius'        => 5000,
		'default_zoom'          => 15,
		'map_height'            => 420,
		'max_results'           => 20,
		'cache_ttl'             => 60,
		'tile_url'              => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
		'tile_attribution'      => '© OpenStreetMap contributors',
		'overpass_endpoint'     => 'https://overpass-api.de/api/interpreter',
		'speed_kmh'             => 25,
		'rounding'              => 'floor',
		'accent_color'          => '#d32f2f',
		'marker_color'          => '#2aa8a8',
		'load_fontawesome'      => 1,
		'load_leaflet'          => 1,
		'label_school'          => 'Trường học',
		'label_supermarket'     => 'Siêu thị',
		'label_park'            => 'Công viên',
		'label_hospital'        => 'Bệnh viện',
		'label_restaurant'      => 'Nhà hàng',
		'label_my_location'     => 'Vị trí của bạn',
		'icon_school'           => 'fa-solid fa-school',
		'icon_supermarket'      => 'fa-solid fa-cart-shopping',
		'icon_park'             => 'fa-solid fa-tree',
		'icon_hospital'         => 'fa-solid fa-hospital',
		'icon_restaurant'       => 'fa-solid fa-utensils',
		'icon_my_location'      => 'fa-solid fa-location-dot',
		'icon_motorcycle'       => 'fa-solid fa-motorcycle',
		'fallback_endpoints'    => "https://overpass.kumi.systems/api/interpreter\nhttps://overpass.nchc.org.tw/api/interpreter",
		'rate_limit_max'        => 30,
		'rate_limit_window_min' => 10,
	);

	$saved = get_option( 'wpli_osm_settings', array() );

	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	return wp_parse_args( $saved, $defaults );
}

/**
 * Register assets.
 */
function wpli_osm_register_assets() {
	wp_register_style(
		'wpli-osm-frontend',
		WPLI_OSM_URL . 'assets/css/wpli-frontend.css',
		array(),
		WPLI_OSM_VERSION
	);

	wp_register_script(
		'wpli-osm-frontend',
		WPLI_OSM_URL . 'assets/js/wpli-frontend.js',
		array(),
		WPLI_OSM_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'wpli_osm_register_assets' );

/**
 * Enqueue assets for shortcode.
 */
function wpli_osm_enqueue_shortcode_assets() {
	$settings = wpli_osm_get_settings();

	if ( ! empty( $settings['load_leaflet'] ) ) {
		wp_enqueue_style(
			'wpli-osm-leaflet',
			'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
			array(),
			'1.9.4'
		);
		wp_enqueue_script(
			'wpli-osm-leaflet',
			'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
			array(),
			'1.9.4',
			true
		);
	}

	if ( ! empty( $settings['load_fontawesome'] ) ) {
		wp_enqueue_style(
			'wpli-osm-fontawesome',
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
			array(),
			'6.5.1'
		);
	}

	wp_enqueue_style( 'wpli-osm-frontend' );
	wp_enqueue_script( 'wpli-osm-frontend' );

	wp_localize_script(
		'wpli-osm-frontend',
		'WPLIOSM',
		array(
			'restUrl'  => esc_url_raw( rest_url( 'wpli/v1/poi' ) ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'settings' => array(
				'accentColor'    => $settings['accent_color'],
				'markerColor'    => $settings['marker_color'],
				'speedKmh'       => (int) $settings['speed_kmh'],
				'rounding'       => $settings['rounding'],
				'motorcycleIcon' => $settings['icon_motorcycle'],
				'maxResults'     => (int) $settings['max_results'],
			),
			'labels'   => array(
				'school'      => $settings['label_school'],
				'supermarket' => $settings['label_supermarket'],
				'park'        => $settings['label_park'],
				'hospital'    => $settings['label_hospital'],
				'restaurant'  => $settings['label_restaurant'],
				'my_location' => $settings['label_my_location'],
			),
			'icons'    => array(
				'school'      => $settings['icon_school'],
				'supermarket' => $settings['icon_supermarket'],
				'park'        => $settings['icon_park'],
				'hospital'    => $settings['icon_hospital'],
				'restaurant'  => $settings['icon_restaurant'],
				'my_location' => $settings['icon_my_location'],
			),
		)
	);
}

/**
 * Helper to enqueue on shortcode render.
 */
function wpli_osm_maybe_enqueue_assets() {
	if ( ! wp_script_is( 'wpli-osm-frontend', 'enqueued' ) ) {
		wpli_osm_enqueue_shortcode_assets();
	}
}
