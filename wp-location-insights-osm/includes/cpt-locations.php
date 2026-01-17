<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'wpli_register_location_cpt' );
add_action( 'add_meta_boxes', 'wpli_register_location_meta_boxes' );
add_action( 'save_post_wpli_location', 'wpli_save_location_meta', 10, 2 );
add_filter( 'manage_wpli_location_posts_columns', 'wpli_location_columns' );
add_action( 'manage_wpli_location_posts_custom_column', 'wpli_location_column_content', 10, 2 );
add_action( 'admin_enqueue_scripts', 'wpli_location_admin_assets' );

/**
 * Register CPT.
 */
function wpli_register_location_cpt() {
	$labels = array(
		'name'               => __( 'Vị trí', 'wp-location-insights-osm' ),
		'singular_name'      => __( 'Vị trí', 'wp-location-insights-osm' ),
		'add_new'            => __( 'Thêm vị trí', 'wp-location-insights-osm' ),
		'add_new_item'       => __( 'Thêm vị trí', 'wp-location-insights-osm' ),
		'edit_item'          => __( 'Chỉnh sửa vị trí', 'wp-location-insights-osm' ),
		'new_item'           => __( 'Vị trí mới', 'wp-location-insights-osm' ),
		'view_item'          => __( 'Xem vị trí', 'wp-location-insights-osm' ),
		'search_items'       => __( 'Tìm vị trí', 'wp-location-insights-osm' ),
		'not_found'          => __( 'Không tìm thấy', 'wp-location-insights-osm' ),
		'not_found_in_trash' => __( 'Không tìm thấy trong thùng rác', 'wp-location-insights-osm' ),
		'menu_name'          => __( 'Vị trí', 'wp-location-insights-osm' ),
	);

	register_post_type(
		'wpli_location',
		array(
			'labels'        => $labels,
			'public'        => false,
			'show_ui'       => true,
			'show_in_menu'  => false,
			'menu_icon'     => 'dashicons-location',
			'supports'      => array( 'title' ),
			'capability_type' => 'post',
		)
	);
}

/**
 * Register meta boxes.
 */
function wpli_register_location_meta_boxes() {
	add_meta_box(
		'wpli_location_coords',
		__( 'Tọa độ', 'wp-location-insights-osm' ),
		'wpli_render_location_coords_metabox',
		'wpli_location',
		'normal',
		'high'
	);

	add_meta_box(
		'wpli_location_config',
		__( 'Cấu hình hiển thị', 'wp-location-insights-osm' ),
		'wpli_render_location_config_metabox',
		'wpli_location',
		'normal',
		'default'
	);

	add_meta_box(
		'wpli_location_shortcode',
		__( 'Shortcode', 'wp-location-insights-osm' ),
		'wpli_render_location_shortcode_metabox',
		'wpli_location',
		'side',
		'default'
	);
}

/**
 * Render coords meta box.
 *
 * @param WP_Post $post Post object.
 */
function wpli_render_location_coords_metabox( $post ) {
	wp_nonce_field( 'wpli_location_meta', 'wpli_location_meta_nonce' );
	$lat = get_post_meta( $post->ID, '_wpli_lat', true );
	$lng = get_post_meta( $post->ID, '_wpli_lng', true );
	?>
	<p>
		<label for="wpli_lat"><?php esc_html_e( 'Latitude', 'wp-location-insights-osm' ); ?></label>
		<input type="text" id="wpli_lat" name="wpli_lat" value="<?php echo esc_attr( $lat ); ?>" class="regular-text" />
	</p>
	<p>
		<label for="wpli_lng"><?php esc_html_e( 'Longitude', 'wp-location-insights-osm' ); ?></label>
		<input type="text" id="wpli_lng" name="wpli_lng" value="<?php echo esc_attr( $lng ); ?>" class="regular-text" />
	</p>
	<div id="wpli-admin-map" class="wpli-admin-map" data-lat="<?php echo esc_attr( $lat ); ?>" data-lng="<?php echo esc_attr( $lng ); ?>"></div>
	<p class="description"><?php esc_html_e( 'Click bản đồ để đặt marker.', 'wp-location-insights-osm' ); ?></p>
	<?php
}

/**
 * Render config meta box.
 *
 * @param WP_Post $post Post object.
 */
function wpli_render_location_config_metabox( $post ) {
	$radius      = get_post_meta( $post->ID, '_wpli_radius', true );
	$zoom        = get_post_meta( $post->ID, '_wpli_zoom', true );
	$height      = get_post_meta( $post->ID, '_wpli_height', true );
	$default_tab = get_post_meta( $post->ID, '_wpli_default_tab', true );
	$notes       = get_post_meta( $post->ID, '_wpli_notes', true );
	?>
	<p>
		<label for="wpli_radius"><?php esc_html_e( 'Bán kính (m)', 'wp-location-insights-osm' ); ?></label>
		<input type="number" id="wpli_radius" name="wpli_radius" value="<?php echo esc_attr( $radius ); ?>" class="small-text" />
		<span class="description"><?php esc_html_e( 'Để trống = dùng mặc định.', 'wp-location-insights-osm' ); ?></span>
	</p>
	<p>
		<label for="wpli_zoom"><?php esc_html_e( 'Zoom', 'wp-location-insights-osm' ); ?></label>
		<input type="number" id="wpli_zoom" name="wpli_zoom" value="<?php echo esc_attr( $zoom ); ?>" class="small-text" />
	</p>
	<p>
		<label for="wpli_height"><?php esc_html_e( 'Chiều cao (px)', 'wp-location-insights-osm' ); ?></label>
		<input type="number" id="wpli_height" name="wpli_height" value="<?php echo esc_attr( $height ); ?>" class="small-text" />
	</p>
	<p>
		<label for="wpli_default_tab"><?php esc_html_e( 'Tab mặc định', 'wp-location-insights-osm' ); ?></label>
		<select id="wpli_default_tab" name="wpli_default_tab">
			<option value=""><?php esc_html_e( 'Mặc định toàn cục', 'wp-location-insights-osm' ); ?></option>
			<option value="school" <?php selected( $default_tab, 'school' ); ?>><?php esc_html_e( 'Trường học', 'wp-location-insights-osm' ); ?></option>
			<option value="supermarket" <?php selected( $default_tab, 'supermarket' ); ?>><?php esc_html_e( 'Siêu thị', 'wp-location-insights-osm' ); ?></option>
			<option value="park" <?php selected( $default_tab, 'park' ); ?>><?php esc_html_e( 'Công viên', 'wp-location-insights-osm' ); ?></option>
			<option value="hospital" <?php selected( $default_tab, 'hospital' ); ?>><?php esc_html_e( 'Bệnh viện', 'wp-location-insights-osm' ); ?></option>
			<option value="restaurant" <?php selected( $default_tab, 'restaurant' ); ?>><?php esc_html_e( 'Nhà hàng', 'wp-location-insights-osm' ); ?></option>
			<option value="my_location" <?php selected( $default_tab, 'my_location' ); ?>><?php esc_html_e( 'Vị trí của bạn', 'wp-location-insights-osm' ); ?></option>
		</select>
	</p>
	<p>
		<label for="wpli_notes"><?php esc_html_e( 'Ghi chú', 'wp-location-insights-osm' ); ?></label>
		<textarea id="wpli_notes" name="wpli_notes" rows="4" class="widefat"><?php echo esc_textarea( $notes ); ?></textarea>
	</p>
	<?php
}

/**
 * Render shortcode meta box.
 *
 * @param WP_Post $post Post object.
 */
function wpli_render_location_shortcode_metabox( $post ) {
	$shortcode = sprintf( '[wpli_location_insights location_id="%d"]', (int) $post->ID );
	?>
	<p>
		<input type="text" class="widefat" readonly value="<?php echo esc_attr( $shortcode ); ?>" />
	</p>
	<p>
		<button type="button" class="button wpli-copy-shortcode" data-shortcode="<?php echo esc_attr( $shortcode ); ?>">
			<?php esc_html_e( 'Copy', 'wp-location-insights-osm' ); ?>
		</button>
	</p>
	<?php
}

/**
 * Save meta fields.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function wpli_save_location_meta( $post_id, $post ) {
	if ( ! isset( $_POST['wpli_location_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpli_location_meta_nonce'] ) ), 'wpli_location_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$lat = isset( $_POST['wpli_lat'] ) ? sanitize_text_field( wp_unslash( $_POST['wpli_lat'] ) ) : '';
	$lng = isset( $_POST['wpli_lng'] ) ? sanitize_text_field( wp_unslash( $_POST['wpli_lng'] ) ) : '';

	update_post_meta( $post_id, '_wpli_lat', $lat );
	update_post_meta( $post_id, '_wpli_lng', $lng );

	$radius = isset( $_POST['wpli_radius'] ) ? absint( $_POST['wpli_radius'] ) : '';
	$zoom   = isset( $_POST['wpli_zoom'] ) ? absint( $_POST['wpli_zoom'] ) : '';
	$height = isset( $_POST['wpli_height'] ) ? absint( $_POST['wpli_height'] ) : '';
	$default_tab = isset( $_POST['wpli_default_tab'] ) ? sanitize_text_field( wp_unslash( $_POST['wpli_default_tab'] ) ) : '';
	$notes       = isset( $_POST['wpli_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wpli_notes'] ) ) : '';

	update_post_meta( $post_id, '_wpli_radius', $radius );
	update_post_meta( $post_id, '_wpli_zoom', $zoom );
	update_post_meta( $post_id, '_wpli_height', $height );
	update_post_meta( $post_id, '_wpli_default_tab', $default_tab );
	update_post_meta( $post_id, '_wpli_notes', $notes );
}

/**
 * Columns for admin list.
 *
 * @param array $columns Columns.
 * @return array
 */
function wpli_location_columns( $columns ) {
	$columns['wpli_shortcode'] = __( 'Shortcode', 'wp-location-insights-osm' );
	return $columns;
}

/**
 * Column content.
 *
 * @param string $column Column name.
 * @param int    $post_id Post ID.
 */
function wpli_location_column_content( $column, $post_id ) {
	if ( 'wpli_shortcode' === $column ) {
		$shortcode = sprintf( '[wpli_location_insights location_id="%d"]', (int) $post_id );
		echo '<input type="text" readonly class="wpli-shortcode-input" value="' . esc_attr( $shortcode ) . '" />';
		echo '<button type="button" class="button wpli-copy-shortcode" data-shortcode="' . esc_attr( $shortcode ) . '">' . esc_html__( 'Copy', 'wp-location-insights-osm' ) . '</button>';
	}
}

/**
 * Enqueue admin assets for location screens.
 *
 * @param string $hook Hook.
 */
function wpli_location_admin_assets( $hook ) {
	if ( 'post.php' !== $hook && 'post-new.php' !== $hook && 'edit.php' !== $hook ) {
		return;
	}
	$screen = get_current_screen();
	if ( empty( $screen ) || 'wpli_location' !== $screen->post_type ) {
		return;
	}

	$settings = wpli_get_settings();

	if ( ! empty( $settings['load_leaflet'] ) ) {
		wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
		wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
	}

	wp_enqueue_style( 'wpli-admin', WPLI_PLUGIN_URL . 'assets/css/wpli-admin.css', array(), WPLI_VERSION );
	wp_enqueue_script( 'wpli-admin', WPLI_PLUGIN_URL . 'assets/js/wpli-admin.js', array( 'leaflet' ), WPLI_VERSION, true );

	wp_localize_script(
		'wpli-admin',
		'wpliAdmin',
		array(
			'tileUrl'         => $settings['tile_url'],
			'tileAttribution' => $settings['tile_attribution'],
			'defaultZoom'     => $settings['default_zoom'],
		)
	);
}
