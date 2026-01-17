<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'wpli_register_admin_menu' );
add_action( 'admin_init', 'wpli_register_settings' );
add_action( 'admin_enqueue_scripts', 'wpli_admin_assets' );

/**
 * Register admin menu.
 */
function wpli_register_admin_menu() {
	add_menu_page(
		__( 'Location Insights', 'wp-location-insights-osm' ),
		__( 'Location Insights', 'wp-location-insights-osm' ),
		'manage_options',
		'wpli-settings',
		'wpli_render_settings_page',
		'dashicons-location',
		30
	);

	add_submenu_page(
		'wpli-settings',
		__( 'Vị trí', 'wp-location-insights-osm' ),
		__( 'Vị trí', 'wp-location-insights-osm' ),
		'edit_posts',
		'edit.php?post_type=wpli_location'
	);

	add_submenu_page(
		'wpli-settings',
		__( 'Cài đặt', 'wp-location-insights-osm' ),
		__( 'Cài đặt', 'wp-location-insights-osm' ),
		'manage_options',
		'wpli-settings',
		'wpli_render_settings_page'
	);
}

/**
 * Register settings.
 */
function wpli_register_settings() {
	register_setting( 'wpli_settings_group', WPLI_OPTION_KEY, 'wpli_sanitize_settings' );

	add_settings_section(
		'wpli_general_section',
		__( 'Cấu hình chung', 'wp-location-insights-osm' ),
		'__return_false',
		'wpli-settings'
	);

	$fields = array(
		'default_radius'          => __( 'Bán kính mặc định (m)', 'wp-location-insights-osm' ),
		'default_zoom'            => __( 'Mức zoom mặc định', 'wp-location-insights-osm' ),
		'default_height'          => __( 'Chiều cao bản đồ (px)', 'wp-location-insights-osm' ),
		'default_max_results'     => __( 'Số kết quả tối đa', 'wp-location-insights-osm' ),
		'cache_ttl'               => __( 'Cache TTL (phút)', 'wp-location-insights-osm' ),
		'overpass_endpoint'       => __( 'Overpass endpoint', 'wp-location-insights-osm' ),
		'tile_url'                => __( 'Tile URL', 'wp-location-insights-osm' ),
		'tile_attribution'        => __( 'Tile attribution', 'wp-location-insights-osm' ),
		'tile_url_fallback'       => __( 'Fallback tile URL (tuỳ chọn)', 'wp-location-insights-osm' ),
		'tile_attribution_fallback' => __( 'Fallback tile attribution (tuỳ chọn)', 'wp-location-insights-osm' ),
		'speed_kmh'               => __( 'Vận tốc ước tính (km/h)', 'wp-location-insights-osm' ),
		'rounding'                => __( 'Phương pháp làm tròn thời gian', 'wp-location-insights-osm' ),
		'accent_color'            => __( 'Màu nhấn tab', 'wp-location-insights-osm' ),
		'marker_color'            => __( 'Màu marker', 'wp-location-insights-osm' ),
		'load_fontawesome'        => __( 'Load Font Awesome CSS', 'wp-location-insights-osm' ),
		'load_leaflet'            => __( 'Load Leaflet assets', 'wp-location-insights-osm' ),
		'label_school'            => __( 'Nhãn tab Trường học', 'wp-location-insights-osm' ),
		'label_supermarket'       => __( 'Nhãn tab Siêu thị', 'wp-location-insights-osm' ),
		'label_park'              => __( 'Nhãn tab Công viên', 'wp-location-insights-osm' ),
		'label_hospital'          => __( 'Nhãn tab Bệnh viện', 'wp-location-insights-osm' ),
		'label_restaurant'        => __( 'Nhãn tab Nhà hàng', 'wp-location-insights-osm' ),
		'label_my_location'       => __( 'Nhãn tab Vị trí của bạn', 'wp-location-insights-osm' ),
		'icon_school'             => __( 'Icon Trường học', 'wp-location-insights-osm' ),
		'icon_supermarket'        => __( 'Icon Siêu thị', 'wp-location-insights-osm' ),
		'icon_park'               => __( 'Icon Công viên', 'wp-location-insights-osm' ),
		'icon_hospital'           => __( 'Icon Bệnh viện', 'wp-location-insights-osm' ),
		'icon_restaurant'         => __( 'Icon Nhà hàng', 'wp-location-insights-osm' ),
		'icon_my_location'        => __( 'Icon Vị trí của bạn', 'wp-location-insights-osm' ),
		'icon_motorcycle'         => __( 'Icon xe máy', 'wp-location-insights-osm' ),
	);

	foreach ( $fields as $key => $label ) {
		add_settings_field(
			$key,
			$label,
			'wpli_render_field',
			'wpli-settings',
			'wpli_general_section',
			array(
				'key' => $key,
			)
		);
	}
}

/**
 * Sanitize settings.
 *
 * @param array $input Input settings.
 * @return array
 */
function wpli_sanitize_settings( $input ) {
	$defaults = wpli_get_default_settings();
	$input    = is_array( $input ) ? $input : array();
	$output   = array();

	$output['default_radius'] = isset( $input['default_radius'] ) ? max( 200, absint( $input['default_radius'] ) ) : $defaults['default_radius'];
	$output['default_zoom']   = isset( $input['default_zoom'] ) ? max( 1, absint( $input['default_zoom'] ) ) : $defaults['default_zoom'];
	$output['default_height'] = isset( $input['default_height'] ) ? max( 200, absint( $input['default_height'] ) ) : $defaults['default_height'];
	$output['default_max_results'] = isset( $input['default_max_results'] ) ? min( 50, max( 1, absint( $input['default_max_results'] ) ) ) : $defaults['default_max_results'];
	$output['cache_ttl']            = isset( $input['cache_ttl'] ) ? max( 1, absint( $input['cache_ttl'] ) ) : $defaults['cache_ttl'];
	$output['overpass_endpoint']    = isset( $input['overpass_endpoint'] ) ? esc_url_raw( $input['overpass_endpoint'] ) : $defaults['overpass_endpoint'];
	$output['tile_url']             = isset( $input['tile_url'] ) ? esc_url_raw( $input['tile_url'] ) : $defaults['tile_url'];
	$output['tile_attribution']     = isset( $input['tile_attribution'] ) ? wp_kses_post( $input['tile_attribution'] ) : $defaults['tile_attribution'];
	$output['tile_url_fallback']    = isset( $input['tile_url_fallback'] ) ? esc_url_raw( $input['tile_url_fallback'] ) : $defaults['tile_url_fallback'];
	$output['tile_attribution_fallback'] = isset( $input['tile_attribution_fallback'] ) ? wp_kses_post( $input['tile_attribution_fallback'] ) : $defaults['tile_attribution_fallback'];
	$output['speed_kmh']    = isset( $input['speed_kmh'] ) ? max( 1, absint( $input['speed_kmh'] ) ) : $defaults['speed_kmh'];
	$output['rounding']     = isset( $input['rounding'] ) && in_array( $input['rounding'], array( 'floor', 'round', 'ceil' ), true ) ? $input['rounding'] : $defaults['rounding'];
	$output['accent_color'] = isset( $input['accent_color'] ) ? sanitize_hex_color( $input['accent_color'] ) : $defaults['accent_color'];
	$output['marker_color'] = isset( $input['marker_color'] ) ? sanitize_hex_color( $input['marker_color'] ) : $defaults['marker_color'];
	$output['load_fontawesome'] = isset( $input['load_fontawesome'] ) ? 1 : 0;
	$output['load_leaflet']     = isset( $input['load_leaflet'] ) ? 1 : 0;

	$labels = array(
		'label_school',
		'label_supermarket',
		'label_park',
		'label_hospital',
		'label_restaurant',
		'label_my_location',
	);
	foreach ( $labels as $label_key ) {
		$output[ $label_key ] = isset( $input[ $label_key ] ) ? sanitize_text_field( $input[ $label_key ] ) : $defaults[ $label_key ];
	}

	$icons = array(
		'icon_school',
		'icon_supermarket',
		'icon_park',
		'icon_hospital',
		'icon_restaurant',
		'icon_my_location',
		'icon_motorcycle',
	);
	foreach ( $icons as $icon_key ) {
		$output[ $icon_key ] = isset( $input[ $icon_key ] ) ? sanitize_text_field( $input[ $icon_key ] ) : $defaults[ $icon_key ];
	}

	return $output;
}

/**
 * Render settings field.
 *
 * @param array $args Field args.
 */
function wpli_render_field( $args ) {
	$settings = wpli_get_settings();
	$key      = $args['key'];
	$value    = isset( $settings[ $key ] ) ? $settings[ $key ] : '';

	switch ( $key ) {
		case 'rounding':
			?>
			<select name="<?php echo esc_attr( WPLI_OPTION_KEY . '[' . $key . ']' ); ?>">
				<option value="floor" <?php selected( $value, 'floor' ); ?>>floor</option>
				<option value="round" <?php selected( $value, 'round' ); ?>>round</option>
				<option value="ceil" <?php selected( $value, 'ceil' ); ?>>ceil</option>
			</select>
			<?php
			break;
		case 'load_fontawesome':
		case 'load_leaflet':
			?>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( WPLI_OPTION_KEY . '[' . $key . ']' ); ?>" value="1" <?php checked( (int) $value, 1 ); ?> />
				<?php esc_html_e( 'Bật', 'wp-location-insights-osm' ); ?>
			</label>
			<?php
			break;
		case 'accent_color':
		case 'marker_color':
			?>
			<input type="text" class="wpli-color-field" name="<?php echo esc_attr( WPLI_OPTION_KEY . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" />
			<?php
			break;
		default:
			$type = 'text';
			if ( is_numeric( $value ) ) {
				$type = 'number';
			}
			?>
			<input type="<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( WPLI_OPTION_KEY . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
			<?php
			break;
	}
}

/**
 * Render settings page.
 */
function wpli_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = wpli_get_settings();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'WP Location Insights (OSM)', 'wp-location-insights-osm' ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'wpli_settings_group' ); ?>
			<?php do_settings_sections( 'wpli-settings' ); ?>
			<?php submit_button(); ?>
		</form>

		<hr />
		<h2><?php esc_html_e( 'Shortcode Generator', 'wp-location-insights-osm' ); ?></h2>
		<div class="wpli-shortcode-generator">
			<p>
				<label><?php esc_html_e( 'Tiêu đề', 'wp-location-insights-osm' ); ?></label>
				<input type="text" id="wpli_sc_title" class="regular-text" />
			</p>
			<p>
				<label><?php esc_html_e( 'Lat', 'wp-location-insights-osm' ); ?></label>
				<input type="text" id="wpli_sc_lat" />
				<label><?php esc_html_e( 'Lng', 'wp-location-insights-osm' ); ?></label>
				<input type="text" id="wpli_sc_lng" />
			</p>
			<p>
				<label><?php esc_html_e( 'Bán kính (m)', 'wp-location-insights-osm' ); ?></label>
				<input type="number" id="wpli_sc_radius" />
				<label><?php esc_html_e( 'Zoom', 'wp-location-insights-osm' ); ?></label>
				<input type="number" id="wpli_sc_zoom" />
				<label><?php esc_html_e( 'Chiều cao (px)', 'wp-location-insights-osm' ); ?></label>
				<input type="number" id="wpli_sc_height" />
			</p>
			<p>
				<label><?php esc_html_e( 'Tab mặc định', 'wp-location-insights-osm' ); ?></label>
				<select id="wpli_sc_tab">
					<option value="school"><?php echo esc_html( $settings['label_school'] ); ?></option>
					<option value="supermarket"><?php echo esc_html( $settings['label_supermarket'] ); ?></option>
					<option value="park"><?php echo esc_html( $settings['label_park'] ); ?></option>
					<option value="hospital"><?php echo esc_html( $settings['label_hospital'] ); ?></option>
					<option value="restaurant"><?php echo esc_html( $settings['label_restaurant'] ); ?></option>
					<option value="my_location"><?php echo esc_html( $settings['label_my_location'] ); ?></option>
				</select>
			</p>
			<p>
				<label><?php esc_html_e( 'Hoặc chọn vị trí đã lưu (ID)', 'wp-location-insights-osm' ); ?></label>
				<input type="number" id="wpli_sc_location" />
			</p>
			<p>
				<button type="button" class="button button-secondary" id="wpli_generate_shortcode"><?php esc_html_e( 'Tạo shortcode', 'wp-location-insights-osm' ); ?></button>
			</p>
			<p>
				<textarea id="wpli_shortcode_output" class="large-text" rows="2" readonly></textarea>
			</p>
		</div>
	</div>
	<?php
}

/**
 * Enqueue admin assets.
 *
 * @param string $hook Page hook.
 */
function wpli_admin_assets( $hook ) {
	if ( false === strpos( $hook, 'wpli' ) && 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}
	wp_enqueue_style( 'wpli-admin', WPLI_PLUGIN_URL . 'assets/css/wpli-admin.css', array(), WPLI_VERSION );
	wp_enqueue_script( 'wpli-admin', WPLI_PLUGIN_URL . 'assets/js/wpli-admin.js', array(), WPLI_VERSION, true );
}
