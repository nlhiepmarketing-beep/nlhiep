<?php
/**
 * Locations management.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Locations post type.
 */
function wpli_osm_register_locations_post_type() {
	$labels = array(
		'name'               => __( 'Vị trí', 'wp-location-insights-osm' ),
		'singular_name'      => __( 'Vị trí', 'wp-location-insights-osm' ),
		'add_new'            => __( 'Thêm mới', 'wp-location-insights-osm' ),
		'add_new_item'       => __( 'Thêm vị trí mới', 'wp-location-insights-osm' ),
		'edit_item'          => __( 'Sửa vị trí', 'wp-location-insights-osm' ),
		'new_item'           => __( 'Vị trí mới', 'wp-location-insights-osm' ),
		'view_item'          => __( 'Xem vị trí', 'wp-location-insights-osm' ),
		'search_items'       => __( 'Tìm vị trí', 'wp-location-insights-osm' ),
		'not_found'          => __( 'Không tìm thấy vị trí.', 'wp-location-insights-osm' ),
		'not_found_in_trash' => __( 'Không có vị trí trong thùng rác.', 'wp-location-insights-osm' ),
		'menu_name'          => __( 'Location Insights', 'wp-location-insights-osm' ),
	);

	$capabilities = array(
		'edit_post'          => 'manage_options',
		'read_post'          => 'manage_options',
		'delete_post'        => 'manage_options',
		'edit_posts'         => 'manage_options',
		'edit_others_posts'  => 'manage_options',
		'publish_posts'      => 'manage_options',
		'read_private_posts' => 'manage_options',
	);

	register_post_type(
		'wpli_location',
		array(
			'labels'             => $labels,
			'public'             => false,
			'show_ui'            => true,
			'show_in_menu'       => 'wpli-osm',
			'capability_type'    => 'wpli_location',
			'capabilities'       => $capabilities,
			'map_meta_cap'       => true,
			'has_archive'        => false,
			'hierarchical'       => false,
			'supports'           => array( 'title' ),
			'menu_position'      => 58,
			'menu_icon'          => 'dashicons-location-alt',
			'exclude_from_search'=> true,
		)
	);
}
add_action( 'init', 'wpli_osm_register_locations_post_type' );

/**
 * Register admin menu container.
 */
function wpli_osm_register_admin_menu() {
	add_menu_page(
		__( 'Location Insights (OSM)', 'wp-location-insights-osm' ),
		__( 'Location Insights', 'wp-location-insights-osm' ),
		'manage_options',
		'wpli-osm',
		'wpli_osm_render_locations_redirect',
		'dashicons-location-alt',
		58
	);

	add_submenu_page(
		'wpli-osm',
		__( 'Danh sách vị trí', 'wp-location-insights-osm' ),
		__( 'Danh sách vị trí', 'wp-location-insights-osm' ),
		'manage_options',
		'edit.php?post_type=wpli_location'
	);

	add_submenu_page(
		'wpli-osm',
		__( 'Cài đặt', 'wp-location-insights-osm' ),
		__( 'Cài đặt', 'wp-location-insights-osm' ),
		'manage_options',
		'wpli-osm-settings',
		'wpli_osm_render_settings_page'
	);
}
add_action( 'admin_menu', 'wpli_osm_register_admin_menu' );

/**
 * Redirect top-level menu to locations list.
 */
function wpli_osm_render_locations_redirect() {
	wp_safe_redirect( admin_url( 'edit.php?post_type=wpli_location' ) );
	exit;
}

/**
 * Add meta box for location details.
 */
function wpli_osm_add_location_metaboxes() {
	add_meta_box(
		'wpli_location_details',
		__( 'Thông tin vị trí', 'wp-location-insights-osm' ),
		'wpli_osm_render_location_metabox',
		'wpli_location',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'wpli_osm_add_location_metaboxes' );

/**
 * Render location metabox.
 */
function wpli_osm_render_location_metabox( $post ) {
	$settings = wpli_osm_get_settings();
	$values   = wpli_osm_get_location_meta( $post->ID, $settings );

	wp_nonce_field( 'wpli_location_save', 'wpli_location_nonce' );
	?>
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row"><label for="wpli_location_lat"><?php esc_html_e( 'Latitude', 'wp-location-insights-osm' ); ?></label></th>
				<td><input name="wpli_location_lat" id="wpli_location_lat" type="text" value="<?php echo esc_attr( $values['lat'] ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="wpli_location_lng"><?php esc_html_e( 'Longitude', 'wp-location-insights-osm' ); ?></label></th>
				<td><input name="wpli_location_lng" id="wpli_location_lng" type="text" value="<?php echo esc_attr( $values['lng'] ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="wpli_location_radius"><?php esc_html_e( 'Radius (meters)', 'wp-location-insights-osm' ); ?></label></th>
				<td><input name="wpli_location_radius" id="wpli_location_radius" type="number" value="<?php echo esc_attr( $values['radius'] ); ?>" class="small-text" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="wpli_location_zoom"><?php esc_html_e( 'Zoom', 'wp-location-insights-osm' ); ?></label></th>
				<td><input name="wpli_location_zoom" id="wpli_location_zoom" type="number" value="<?php echo esc_attr( $values['zoom'] ); ?>" class="small-text" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="wpli_location_height"><?php esc_html_e( 'Height (px)', 'wp-location-insights-osm' ); ?></label></th>
				<td><input name="wpli_location_height" id="wpli_location_height" type="number" value="<?php echo esc_attr( $values['height'] ); ?>" class="small-text" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="wpli_location_default_tab"><?php esc_html_e( 'Default tab', 'wp-location-insights-osm' ); ?></label></th>
				<td>
					<select name="wpli_location_default_tab" id="wpli_location_default_tab">
						<?php foreach ( wpli_osm_get_allowed_tabs() as $tab_key => $tab_label ) : ?>
							<option value="<?php echo esc_attr( $tab_key ); ?>" <?php selected( $values['default_tab'], $tab_key ); ?>><?php echo esc_html( $tab_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</tbody>
	</table>
	<p>
		<?php esc_html_e( 'Shortcode:', 'wp-location-insights-osm' ); ?>
		<code>[wpli_location_insights location_id="<?php echo esc_attr( $post->ID ); ?>"]</code>
	</p>
	<?php
}

/**
 * Save location meta.
 */
function wpli_osm_save_location_meta( $post_id ) {
	if ( ! isset( $_POST['wpli_location_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['wpli_location_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'wpli_location_save' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'manage_options', $post_id ) ) {
		return;
	}

	$fields = array(
		'lat'         => isset( $_POST['wpli_location_lat'] ) ? sanitize_text_field( wp_unslash( $_POST['wpli_location_lat'] ) ) : '',
		'lng'         => isset( $_POST['wpli_location_lng'] ) ? sanitize_text_field( wp_unslash( $_POST['wpli_location_lng'] ) ) : '',
		'radius'      => isset( $_POST['wpli_location_radius'] ) ? absint( $_POST['wpli_location_radius'] ) : 0,
		'zoom'        => isset( $_POST['wpli_location_zoom'] ) ? absint( $_POST['wpli_location_zoom'] ) : 0,
		'height'      => isset( $_POST['wpli_location_height'] ) ? absint( $_POST['wpli_location_height'] ) : 0,
		'default_tab' => isset( $_POST['wpli_location_default_tab'] ) ? sanitize_text_field( wp_unslash( $_POST['wpli_location_default_tab'] ) ) : '',
	);

	update_post_meta( $post_id, '_wpli_lat', $fields['lat'] );
	update_post_meta( $post_id, '_wpli_lng', $fields['lng'] );
	update_post_meta( $post_id, '_wpli_radius', $fields['radius'] );
	update_post_meta( $post_id, '_wpli_zoom', $fields['zoom'] );
	update_post_meta( $post_id, '_wpli_height', $fields['height'] );
	update_post_meta( $post_id, '_wpli_default_tab', $fields['default_tab'] );
}
add_action( 'save_post_wpli_location', 'wpli_osm_save_location_meta' );

/**
 * Allowed tabs.
 */
function wpli_osm_get_allowed_tabs() {
	return array(
		'school'      => __( 'Trường học', 'wp-location-insights-osm' ),
		'supermarket' => __( 'Siêu thị', 'wp-location-insights-osm' ),
		'park'        => __( 'Công viên', 'wp-location-insights-osm' ),
		'hospital'    => __( 'Bệnh viện', 'wp-location-insights-osm' ),
		'restaurant'  => __( 'Nhà hàng', 'wp-location-insights-osm' ),
		'my_location' => __( 'Vị trí của bạn', 'wp-location-insights-osm' ),
	);
}

/**
 * Get location meta with defaults.
 */
function wpli_osm_get_location_meta( $post_id, $settings ) {
	return array(
		'lat'         => get_post_meta( $post_id, '_wpli_lat', true ),
		'lng'         => get_post_meta( $post_id, '_wpli_lng', true ),
		'radius'      => (int) get_post_meta( $post_id, '_wpli_radius', true ) ?: (int) $settings['default_radius'],
		'zoom'        => (int) get_post_meta( $post_id, '_wpli_zoom', true ) ?: (int) $settings['default_zoom'],
		'height'      => (int) get_post_meta( $post_id, '_wpli_height', true ) ?: (int) $settings['map_height'],
		'default_tab' => get_post_meta( $post_id, '_wpli_default_tab', true ) ?: 'school',
	);
}

/**
 * Add custom columns to location list.
 */
function wpli_osm_location_columns( $columns ) {
	$columns['shortcode'] = __( 'Shortcode', 'wp-location-insights-osm' );
	return $columns;
}
add_filter( 'manage_wpli_location_posts_columns', 'wpli_osm_location_columns' );

/**
 * Render custom columns.
 */
function wpli_osm_location_column_content( $column, $post_id ) {
	if ( 'shortcode' === $column ) {
		echo '<code>[wpli_location_insights location_id="' . esc_html( $post_id ) . '"]</code>';
	}
}
add_action( 'manage_wpli_location_posts_custom_column', 'wpli_osm_location_column_content', 10, 2 );
