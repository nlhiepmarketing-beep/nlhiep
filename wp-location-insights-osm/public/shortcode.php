<?php
/**
 * Shortcode.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render shortcode.
 */
function wpli_osm_render_shortcode( $atts ) {
	$settings = wpli_osm_get_settings();

	$atts = shortcode_atts(
		array(
			'title'       => '',
			'lat'         => '',
			'lng'         => '',
			'radius'      => '',
			'zoom'        => '',
			'height'      => '',
			'default_tab' => 'school',
		),
		$atts,
		'wpli_location_insights'
	);

	$lat = is_numeric( $atts['lat'] ) ? (float) $atts['lat'] : null;
	$lng = is_numeric( $atts['lng'] ) ? (float) $atts['lng'] : null;

	if ( null === $lat || null === $lng ) {
		return '<div class="wpli-error">' . esc_html__( 'Thiếu toạ độ hợp lệ cho shortcode.', 'wp-location-insights-osm' ) . '</div>';
	}

	$radius = absint( $atts['radius'] );
	if ( 0 === $radius ) {
		$radius = absint( $settings['default_radius'] );
	}

	$zoom = absint( $atts['zoom'] );
	if ( 0 === $zoom ) {
		$zoom = absint( $settings['default_zoom'] );
	}

	$height = absint( $atts['height'] );
	if ( 0 === $height ) {
		$height = absint( $settings['map_height'] );
	}

	$default_tab = sanitize_text_field( $atts['default_tab'] );
	$allowed_tabs = array( 'school', 'supermarket', 'park', 'hospital', 'restaurant', 'my_location' );
	if ( ! in_array( $default_tab, $allowed_tabs, true ) ) {
		$default_tab = 'school';
	}

	$container_id = 'wpli-' . wp_generate_uuid4();

	wpli_osm_maybe_enqueue_assets();

	ob_start();
	?>
	<div class="wpli-wrapper" id="<?php echo esc_attr( $container_id ); ?>"
		data-lat="<?php echo esc_attr( $lat ); ?>"
		data-lng="<?php echo esc_attr( $lng ); ?>"
		data-radius="<?php echo esc_attr( $radius ); ?>"
		data-zoom="<?php echo esc_attr( $zoom ); ?>"
		data-height="<?php echo esc_attr( $height ); ?>"
		data-default-tab="<?php echo esc_attr( $default_tab ); ?>"
		data-tile-url="<?php echo esc_attr( $settings['tile_url'] ); ?>"
		data-tile-attribution="<?php echo esc_attr( $settings['tile_attribution'] ); ?>"
		data-title="<?php echo esc_attr( $atts['title'] ); ?>">
		<div class="wpli-map" style="height: <?php echo esc_attr( $height ); ?>px;"></div>
		<div class="wpli-panel">
			<?php if ( ! empty( $atts['title'] ) ) : ?>
				<h3 class="wpli-title"><?php echo esc_html( $atts['title'] ); ?></h3>
			<?php endif; ?>
			<div class="wpli-tabs" role="tablist"></div>
			<div class="wpli-summary" aria-live="polite"></div>
			<div class="wpli-list" role="list"></div>
			<div class="wpli-status" aria-live="polite"></div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'wpli_location_insights', 'wpli_osm_render_shortcode' );
