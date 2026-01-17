<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPLI_Plugin' ) ) {
	class WPLI_Plugin {
		private static $instance = null;

		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		private function __construct() {
			$this->includes();
			$this->hooks();
		}

		private function includes() {
			require_once WPLI_PLUGIN_DIR . 'includes/class-wpli-admin.php';
			require_once WPLI_PLUGIN_DIR . 'includes/class-wpli-rest.php';
			require_once WPLI_PLUGIN_DIR . 'includes/class-wpli-cpt.php';
			require_once WPLI_PLUGIN_DIR . 'public/class-wpli-shortcode.php';
		}

		private function hooks() {
			register_activation_hook( WPLI_PLUGIN_DIR . 'wp-location-insights-osm.php', array( $this, 'activate' ) );
		}

		public function activate() {
			$defaults = wpli_get_default_settings();
			$settings = get_option( WPLI_OPTION_KEY );
			if ( ! is_array( $settings ) ) {
				update_option( WPLI_OPTION_KEY, $defaults );
				return;
			}
			update_option( WPLI_OPTION_KEY, wp_parse_args( $settings, $defaults ) );
		}
	}
}

if ( ! function_exists( 'wpli_get_default_settings' ) ) {
	function wpli_get_default_settings() {
		return array(
			'default_radius'            => 5000,
			'default_zoom'              => 15,
			'default_height'            => 420,
			'default_max_results'       => 20,
			'cache_ttl'                 => 60,
			'overpass_endpoint'         => 'https://overpass-api.de/api/interpreter',
			'tile_url'                  => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
			'tile_attribution'          => '© OpenStreetMap contributors',
			'tile_url_fallback'         => '',
			'tile_attribution_fallback' => '',
			'speed_kmh'                 => 25,
			'rounding'                  => 'floor',
			'accent_color'              => '#d32f2f',
			'marker_color'              => '#2aa8a8',
			'load_fontawesome'          => 1,
			'load_leaflet'              => 1,
			'always_enqueue'            => 0,
			'label_school'              => 'Trường học',
			'label_supermarket'         => 'Siêu thị',
			'label_park'                => 'Công viên',
			'label_hospital'            => 'Bệnh viện',
			'label_restaurant'          => 'Nhà hàng',
			'label_my_location'         => 'Vị trí của bạn',
			'icon_school'               => 'fa-solid fa-school',
			'icon_supermarket'          => 'fa-solid fa-cart-shopping',
			'icon_park'                 => 'fa-solid fa-tree',
			'icon_hospital'             => 'fa-solid fa-hospital',
			'icon_restaurant'           => 'fa-solid fa-utensils',
			'icon_my_location'          => 'fa-solid fa-location-dot',
			'icon_motorcycle'           => 'fa-solid fa-motorcycle',
		);
	}
}

if ( ! function_exists( 'wpli_get_settings' ) ) {
	function wpli_get_settings() {
		$defaults = wpli_get_default_settings();
		$settings = get_option( WPLI_OPTION_KEY, array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		return wp_parse_args( $settings, $defaults );
	}
}
