<?php
/**
 * Admin settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register settings.
 */
function wpli_osm_register_settings() {
	register_setting(
		'wpli_osm_settings_group',
		'wpli_osm_settings',
		array(
			'sanitize_callback' => 'wpli_osm_sanitize_settings',
		)
	);
}
add_action( 'admin_init', 'wpli_osm_register_settings' );

/**
 * Add settings page.
 */
function wpli_osm_add_settings_page() {
	add_options_page(
		__( 'Location Insights (OSM)', 'wp-location-insights-osm' ),
		__( 'Location Insights (OSM)', 'wp-location-insights-osm' ),
		'manage_options',
		'wpli-osm-settings',
		'wpli_osm_render_settings_page'
	);
}
add_action( 'admin_menu', 'wpli_osm_add_settings_page' );

/**
 * Enqueue admin assets.
 */
function wpli_osm_admin_assets( $hook ) {
	if ( 'settings_page_wpli-osm-settings' !== $hook ) {
		return;
	}

	wp_enqueue_style(
		'wpli-osm-admin',
		WPLI_OSM_URL . 'assets/css/wpli-admin.css',
		array(),
		WPLI_OSM_VERSION
	);

	wp_enqueue_script(
		'wpli-osm-admin',
		WPLI_OSM_URL . 'assets/js/wpli-admin.js',
		array(),
		WPLI_OSM_VERSION,
		true
	);

	wp_localize_script(
		'wpli-osm-admin',
		'WPLIOSMAdmin',
		array()
	);
}
add_action( 'admin_enqueue_scripts', 'wpli_osm_admin_assets' );

/**
 * Sanitize settings.
 *
 * @param array $input Raw input.
 * @return array
 */
function wpli_osm_sanitize_settings( $input ) {
	$sanitized = array();
	$defaults  = wpli_osm_get_settings();

	$sanitized['default_radius']        = isset( $input['default_radius'] ) ? max( 200, absint( $input['default_radius'] ) ) : $defaults['default_radius'];
	$sanitized['default_zoom']          = isset( $input['default_zoom'] ) ? max( 1, absint( $input['default_zoom'] ) ) : $defaults['default_zoom'];
	$sanitized['map_height']            = isset( $input['map_height'] ) ? max( 200, absint( $input['map_height'] ) ) : $defaults['map_height'];
	$sanitized['max_results']           = isset( $input['max_results'] ) ? min( 50, max( 1, absint( $input['max_results'] ) ) ) : $defaults['max_results'];
	$sanitized['cache_ttl']             = isset( $input['cache_ttl'] ) ? max( 1, absint( $input['cache_ttl'] ) ) : $defaults['cache_ttl'];
	$sanitized['tile_url']              = isset( $input['tile_url'] ) ? esc_url_raw( $input['tile_url'] ) : $defaults['tile_url'];
	$sanitized['tile_attribution']      = isset( $input['tile_attribution'] ) ? wp_kses_post( $input['tile_attribution'] ) : $defaults['tile_attribution'];
	$sanitized['overpass_endpoint']     = isset( $input['overpass_endpoint'] ) ? esc_url_raw( $input['overpass_endpoint'] ) : $defaults['overpass_endpoint'];
	$sanitized['speed_kmh']             = isset( $input['speed_kmh'] ) ? max( 1, absint( $input['speed_kmh'] ) ) : $defaults['speed_kmh'];
	$sanitized['rounding']              = isset( $input['rounding'] ) && in_array( $input['rounding'], array( 'floor', 'round', 'ceil' ), true ) ? $input['rounding'] : $defaults['rounding'];
	$sanitized['accent_color']          = isset( $input['accent_color'] ) ? sanitize_hex_color( $input['accent_color'] ) : $defaults['accent_color'];
	$sanitized['marker_color']          = isset( $input['marker_color'] ) ? sanitize_hex_color( $input['marker_color'] ) : $defaults['marker_color'];
	$sanitized['load_fontawesome']      = isset( $input['load_fontawesome'] ) ? 1 : 0;
	$sanitized['load_leaflet']          = isset( $input['load_leaflet'] ) ? 1 : 0;
	$sanitized['label_school']          = isset( $input['label_school'] ) ? sanitize_text_field( $input['label_school'] ) : $defaults['label_school'];
	$sanitized['label_supermarket']     = isset( $input['label_supermarket'] ) ? sanitize_text_field( $input['label_supermarket'] ) : $defaults['label_supermarket'];
	$sanitized['label_park']            = isset( $input['label_park'] ) ? sanitize_text_field( $input['label_park'] ) : $defaults['label_park'];
	$sanitized['label_hospital']        = isset( $input['label_hospital'] ) ? sanitize_text_field( $input['label_hospital'] ) : $defaults['label_hospital'];
	$sanitized['label_restaurant']      = isset( $input['label_restaurant'] ) ? sanitize_text_field( $input['label_restaurant'] ) : $defaults['label_restaurant'];
	$sanitized['label_my_location']     = isset( $input['label_my_location'] ) ? sanitize_text_field( $input['label_my_location'] ) : $defaults['label_my_location'];
	$sanitized['icon_school']           = isset( $input['icon_school'] ) ? sanitize_text_field( $input['icon_school'] ) : $defaults['icon_school'];
	$sanitized['icon_supermarket']      = isset( $input['icon_supermarket'] ) ? sanitize_text_field( $input['icon_supermarket'] ) : $defaults['icon_supermarket'];
	$sanitized['icon_park']             = isset( $input['icon_park'] ) ? sanitize_text_field( $input['icon_park'] ) : $defaults['icon_park'];
	$sanitized['icon_hospital']         = isset( $input['icon_hospital'] ) ? sanitize_text_field( $input['icon_hospital'] ) : $defaults['icon_hospital'];
	$sanitized['icon_restaurant']       = isset( $input['icon_restaurant'] ) ? sanitize_text_field( $input['icon_restaurant'] ) : $defaults['icon_restaurant'];
	$sanitized['icon_my_location']      = isset( $input['icon_my_location'] ) ? sanitize_text_field( $input['icon_my_location'] ) : $defaults['icon_my_location'];
	$sanitized['icon_motorcycle']       = isset( $input['icon_motorcycle'] ) ? sanitize_text_field( $input['icon_motorcycle'] ) : $defaults['icon_motorcycle'];
	$sanitized['fallback_endpoints']    = isset( $input['fallback_endpoints'] ) ? sanitize_textarea_field( $input['fallback_endpoints'] ) : $defaults['fallback_endpoints'];
	$sanitized['rate_limit_max']        = isset( $input['rate_limit_max'] ) ? max( 1, absint( $input['rate_limit_max'] ) ) : $defaults['rate_limit_max'];
	$sanitized['rate_limit_window_min'] = isset( $input['rate_limit_window_min'] ) ? max( 1, absint( $input['rate_limit_window_min'] ) ) : $defaults['rate_limit_window_min'];

	return $sanitized;
}

/**
 * Render settings page.
 */
function wpli_osm_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = wpli_osm_get_settings();
	?>
	<div class="wrap wpli-admin">
		<h1><?php esc_html_e( 'WP Location Insights (OSM)', 'wp-location-insights-osm' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'wpli_osm_settings_group' ); ?>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="wpli_default_radius"><?php esc_html_e( 'Default radius (meters)', 'wp-location-insights-osm' ); ?></label></th>
						<td><input name="wpli_osm_settings[default_radius]" id="wpli_default_radius" type="number" value="<?php echo esc_attr( $settings['default_radius'] ); ?>" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_default_zoom"><?php esc_html_e( 'Default zoom', 'wp-location-insights-osm' ); ?></label></th>
						<td><input name="wpli_osm_settings[default_zoom]" id="wpli_default_zoom" type="number" value="<?php echo esc_attr( $settings['default_zoom'] ); ?>" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_map_height"><?php esc_html_e( 'Map height (px)', 'wp-location-insights-osm' ); ?></label></th>
						<td><input name="wpli_osm_settings[map_height]" id="wpli_map_height" type="number" value="<?php echo esc_attr( $settings['map_height'] ); ?>" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_max_results"><?php esc_html_e( 'Max results', 'wp-location-insights-osm' ); ?></label></th>
						<td><input name="wpli_osm_settings[max_results]" id="wpli_max_results" type="number" value="<?php echo esc_attr( $settings['max_results'] ); ?>" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_cache_ttl"><?php esc_html_e( 'Cache TTL (minutes)', 'wp-location-insights-osm' ); ?></label></th>
						<td><input name="wpli_osm_settings[cache_ttl]" id="wpli_cache_ttl" type="number" value="<?php echo esc_attr( $settings['cache_ttl'] ); ?>" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_tile_url"><?php esc_html_e( 'Tile URL template', 'wp-location-insights-osm' ); ?></label></th>
						<td><input name="wpli_osm_settings[tile_url]" id="wpli_tile_url" type="text" value="<?php echo esc_attr( $settings['tile_url'] ); ?>" class="regular-text code" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_tile_attribution"><?php esc_html_e( 'Tile attribution', 'wp-location-insights-osm' ); ?></label></th>
						<td><textarea name="wpli_osm_settings[tile_attribution]" id="wpli_tile_attribution" class="large-text" rows="2"><?php echo esc_textarea( $settings['tile_attribution'] ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_overpass_endpoint"><?php esc_html_e( 'Overpass endpoint URL', 'wp-location-insights-osm' ); ?></label></th>
						<td><input name="wpli_osm_settings[overpass_endpoint]" id="wpli_overpass_endpoint" type="text" value="<?php echo esc_attr( $settings['overpass_endpoint'] ); ?>" class="regular-text code" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_fallback_endpoints"><?php esc_html_e( 'Fallback Overpass endpoints (one per line)', 'wp-location-insights-osm' ); ?></label></th>
						<td><textarea name="wpli_osm_settings[fallback_endpoints]" id="wpli_fallback_endpoints" class="large-text code" rows="3"><?php echo esc_textarea( $settings['fallback_endpoints'] ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_speed_kmh"><?php esc_html_e( 'Estimated speed (km/h)', 'wp-location-insights-osm' ); ?></label></th>
						<td><input name="wpli_osm_settings[speed_kmh]" id="wpli_speed_kmh" type="number" value="<?php echo esc_attr( $settings['speed_kmh'] ); ?>" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_rounding"><?php esc_html_e( 'Rounding method', 'wp-location-insights-osm' ); ?></label></th>
						<td>
							<select name="wpli_osm_settings[rounding]" id="wpli_rounding">
								<?php foreach ( array( 'floor', 'round', 'ceil' ) as $rounding ) : ?>
									<option value="<?php echo esc_attr( $rounding ); ?>" <?php selected( $settings['rounding'], $rounding ); ?>><?php echo esc_html( ucfirst( $rounding ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_accent_color"><?php esc_html_e( 'Accent color', 'wp-location-insights-osm' ); ?></label></th>
						<td><input name="wpli_osm_settings[accent_color]" id="wpli_accent_color" type="text" value="<?php echo esc_attr( $settings['accent_color'] ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_marker_color"><?php esc_html_e( 'Marker theme color', 'wp-location-insights-osm' ); ?></label></th>
						<td><input name="wpli_osm_settings[marker_color]" id="wpli_marker_color" type="text" value="<?php echo esc_attr( $settings['marker_color'] ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Load Font Awesome CSS', 'wp-location-insights-osm' ); ?></th>
						<td><label><input name="wpli_osm_settings[load_fontawesome]" type="checkbox" value="1" <?php checked( $settings['load_fontawesome'], 1 ); ?> /> <?php esc_html_e( 'Enable', 'wp-location-insights-osm' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Load Leaflet assets', 'wp-location-insights-osm' ); ?></th>
						<td><label><input name="wpli_osm_settings[load_leaflet]" type="checkbox" value="1" <?php checked( $settings['load_leaflet'], 1 ); ?> /> <?php esc_html_e( 'Enable', 'wp-location-insights-osm' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Rate limit max (requests per window)', 'wp-location-insights-osm' ); ?></th>
						<td><input name="wpli_osm_settings[rate_limit_max]" type="number" value="<?php echo esc_attr( $settings['rate_limit_max'] ); ?>" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Rate limit window (minutes)', 'wp-location-insights-osm' ); ?></th>
						<td><input name="wpli_osm_settings[rate_limit_window_min]" type="number" value="<?php echo esc_attr( $settings['rate_limit_window_min'] ); ?>" class="small-text" /></td>
					</tr>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Tab Labels (Vietnamese)', 'wp-location-insights-osm' ); ?></h2>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="wpli_label_school"><?php esc_html_e( 'School label', 'wp-location-insights-osm' ); ?></label></th>
						<td><input name="wpli_osm_settings[label_school]" id="wpli_label_school" type="text" value="<?php echo esc_attr( $settings['label_school'] ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_label_supermarket"><?php esc_html_e( 'Supermarket label', 'wp-location-insights-osm' ); ?></label></th>
						<td><input name="wpli_osm_settings[label_supermarket]" id="wpli_label_supermarket" type="text" value="<?php echo esc_attr( $settings['label_supermarket'] ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_label_park"><?php esc_html_e( 'Park label', 'wp-location-insights-osm' ); ?></label></th>
						<td><input name="wpli_osm_settings[label_park]" id="wpli_label_park" type="text" value="<?php echo esc_attr( $settings['label_park'] ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_label_hospital"><?php esc_html_e( 'Hospital label', 'wp-location-insights-osm' ); ?></label></th>
						<td><input name="wpli_osm_settings[label_hospital]" id="wpli_label_hospital" type="text" value="<?php echo esc_attr( $settings['label_hospital'] ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_label_restaurant"><?php esc_html_e( 'Restaurant label', 'wp-location-insights-osm' ); ?></label></th>
						<td><input name="wpli_osm_settings[label_restaurant]" id="wpli_label_restaurant" type="text" value="<?php echo esc_attr( $settings['label_restaurant'] ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_label_my_location"><?php esc_html_e( 'My location label', 'wp-location-insights-osm' ); ?></label></th>
						<td><input name="wpli_osm_settings[label_my_location]" id="wpli_label_my_location" type="text" value="<?php echo esc_attr( $settings['label_my_location'] ); ?>" class="regular-text" /></td>
					</tr>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Font Awesome Icon Classes', 'wp-location-insights-osm' ); ?></h2>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="wpli_icon_school"><?php esc_html_e( 'School icon', 'wp-location-insights-osm' ); ?></label></th>
						<td><input name="wpli_osm_settings[icon_school]" id="wpli_icon_school" type="text" value="<?php echo esc_attr( $settings['icon_school'] ); ?>" class="regular-text code" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_icon_supermarket"><?php esc_html_e( 'Supermarket icon', 'wp-location-insights-osm' ); ?></label></th>
						<td><input name="wpli_osm_settings[icon_supermarket]" id="wpli_icon_supermarket" type="text" value="<?php echo esc_attr( $settings['icon_supermarket'] ); ?>" class="regular-text code" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_icon_park"><?php esc_html_e( 'Park icon', 'wp-location-insights-osm' ); ?></label></th>
						<td><input name="wpli_osm_settings[icon_park]" id="wpli_icon_park" type="text" value="<?php echo esc_attr( $settings['icon_park'] ); ?>" class="regular-text code" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_icon_hospital"><?php esc_html_e( 'Hospital icon', 'wp-location-insights-osm' ); ?></label></th>
						<td><input name="wpli_osm_settings[icon_hospital]" id="wpli_icon_hospital" type="text" value="<?php echo esc_attr( $settings['icon_hospital'] ); ?>" class="regular-text code" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_icon_restaurant"><?php esc_html_e( 'Restaurant icon', 'wp-location-insights-osm' ); ?></label></th>
						<td><input name="wpli_osm_settings[icon_restaurant]" id="wpli_icon_restaurant" type="text" value="<?php echo esc_attr( $settings['icon_restaurant'] ); ?>" class="regular-text code" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_icon_my_location"><?php esc_html_e( 'My location icon', 'wp-location-insights-osm' ); ?></label></th>
						<td><input name="wpli_osm_settings[icon_my_location]" id="wpli_icon_my_location" type="text" value="<?php echo esc_attr( $settings['icon_my_location'] ); ?>" class="regular-text code" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_icon_motorcycle"><?php esc_html_e( 'Motorcycle icon', 'wp-location-insights-osm' ); ?></label></th>
						<td><input name="wpli_osm_settings[icon_motorcycle]" id="wpli_icon_motorcycle" type="text" value="<?php echo esc_attr( $settings['icon_motorcycle'] ); ?>" class="regular-text code" /></td>
					</tr>
				</tbody>
			</table>

			<?php submit_button(); ?>
		</form>

		<div class="wpli-shortcode-generator">
			<h2><?php esc_html_e( 'Shortcode Generator', 'wp-location-insights-osm' ); ?></h2>
			<p><?php esc_html_e( 'Use this to generate a shortcode for Flatsome UX Builder.', 'wp-location-insights-osm' ); ?></p>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="wpli_sc_title"><?php esc_html_e( 'Title', 'wp-location-insights-osm' ); ?></label></th>
						<td><input id="wpli_sc_title" type="text" class="regular-text" placeholder="La Pura" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_sc_lat"><?php esc_html_e( 'Latitude', 'wp-location-insights-osm' ); ?></label></th>
						<td><input id="wpli_sc_lat" type="text" class="regular-text" placeholder="10.915919" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_sc_lng"><?php esc_html_e( 'Longitude', 'wp-location-insights-osm' ); ?></label></th>
						<td><input id="wpli_sc_lng" type="text" class="regular-text" placeholder="106.713676" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_sc_radius"><?php esc_html_e( 'Radius (meters)', 'wp-location-insights-osm' ); ?></label></th>
						<td><input id="wpli_sc_radius" type="number" class="regular-text" placeholder="2000" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="wpli_sc_zoom"><?php esc_html_e( 'Zoom', 'wp-location-insights-osm' ); ?></label></th>
						<td><input id="wpli_sc_zoom" type="number" class="regular-text" placeholder="15" /></td>
					</tr>
				</tbody>
			</table>
			<p><strong><?php esc_html_e( 'Shortcode', 'wp-location-insights-osm' ); ?></strong></p>
			<textarea id="wpli_sc_output" class="large-text code" rows="2" readonly>[wpli_location_insights]</textarea>
		</div>
	</div>
	<?php
}
