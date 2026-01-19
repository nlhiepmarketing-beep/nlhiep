<?php
/**
 * Plugin Name: WP Location Insights (OSM)
 * Description: Location Insights block with OpenStreetMap + Overpass API.
 * Version: 1.3.0
 * Author: OpenAI
 * Text Domain: wp-location-insights-osm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPLI_VERSION', '1.3.0' );
define( 'WPLI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPLI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPLI_OPTION_KEY', 'wpli_settings' );
define( 'WPLI_RATE_LIMIT_MAX', 30 );
define( 'WPLI_RATE_LIMIT_WINDOW', 600 );

require_once WPLI_PLUGIN_DIR . 'includes/class-wpli-plugin.php';

if ( class_exists( 'WPLI_Plugin' ) ) {
	WPLI_Plugin::get_instance();
}
