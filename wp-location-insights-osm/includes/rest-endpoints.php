<?php
/**
 * REST endpoints.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register REST routes.
 */
function wpli_osm_register_rest_routes() {
	register_rest_route(
		'wpli/v1',
		'/poi',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'wpli_osm_handle_poi_request',
			'permission_callback' => '__return_true',
			'args'                => array(
				'lat'    => array(
					'required'          => true,
					'validate_callback' => 'wpli_osm_validate_lat',
				),
				'lng'    => array(
					'required'          => true,
					'validate_callback' => 'wpli_osm_validate_lng',
				),
				'radius' => array(
					'required'          => false,
					'validate_callback' => 'wpli_osm_validate_radius',
				),
				'limit'  => array(
					'required'          => false,
					'validate_callback' => 'wpli_osm_validate_limit',
				),
				'type'   => array(
					'required'          => true,
					'validate_callback' => 'wpli_osm_validate_type',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'wpli_osm_register_rest_routes' );

/**
 * Validate latitude.
 */
function wpli_osm_validate_lat( $value ) {
	$lat = floatval( $value );
	return $lat >= -90 && $lat <= 90;
}

/**
 * Validate longitude.
 */
function wpli_osm_validate_lng( $value ) {
	$lng = floatval( $value );
	return $lng >= -180 && $lng <= 180;
}

/**
 * Validate radius.
 */
function wpli_osm_validate_radius( $value ) {
	$radius = absint( $value );
	return $radius >= 200 && $radius <= 10000;
}

/**
 * Validate limit.
 */
function wpli_osm_validate_limit( $value ) {
	$limit = absint( $value );
	return $limit >= 1 && $limit <= 50;
}

/**
 * Validate type.
 */
function wpli_osm_validate_type( $value ) {
	$allowed = array( 'school', 'supermarket', 'park', 'hospital', 'restaurant' );
	return in_array( $value, $allowed, true );
}

/**
 * Handle POI requests.
 */
function wpli_osm_handle_poi_request( WP_REST_Request $request ) {
	$settings = wpli_osm_get_settings();

	$rate_limit_check = wpli_osm_check_rate_limit( $settings );
	if ( is_wp_error( $rate_limit_check ) ) {
		return $rate_limit_check;
	}

	$lat    = floatval( $request['lat'] );
	$lng    = floatval( $request['lng'] );
	$radius = absint( $request['radius'] );
	$limit  = absint( $request['limit'] );
	$type   = sanitize_text_field( $request['type'] );

	if ( 0 === $radius ) {
		$radius = absint( $settings['default_radius'] );
	}

	if ( 0 === $limit ) {
		$limit = absint( $settings['max_results'] );
	}

	$cache_key = 'wpli_osm_' . md5( $lat . '|' . $lng . '|' . $radius . '|' . $type . '|' . $limit );
	$cached    = get_transient( $cache_key );

	if ( false !== $cached ) {
		return rest_ensure_response( $cached );
	}

	$query = wpli_osm_build_overpass_query( $type, $lat, $lng, $radius, $limit );

	$overpass_result = wpli_osm_fetch_overpass_data( $query, $settings );

	if ( is_wp_error( $overpass_result ) ) {
		return $overpass_result;
	}

	$items = wpli_osm_parse_overpass_elements( $overpass_result );

	$response = array(
		'count' => count( $items ),
		'items' => $items,
	);

	set_transient( $cache_key, $response, absint( $settings['cache_ttl'] ) * MINUTE_IN_SECONDS );

	return rest_ensure_response( $response );
}

/**
 * Rate limit.
 */
function wpli_osm_check_rate_limit( $settings ) {
	$max     = isset( $settings['rate_limit_max'] ) ? absint( $settings['rate_limit_max'] ) : 30;
	$window  = isset( $settings['rate_limit_window_min'] ) ? absint( $settings['rate_limit_window_min'] ) : 10;
	$ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	$key     = 'wpli_rate_' . md5( $ip );
	$counter = get_transient( $key );

	if ( false === $counter ) {
		set_transient( $key, 1, $window * MINUTE_IN_SECONDS );
		return true;
	}

	if ( $counter >= $max ) {
		return new WP_Error(
			'wpli_rate_limited',
			__( 'Quá nhiều yêu cầu. Vui lòng thử lại sau.', 'wp-location-insights-osm' ),
			array( 'status' => 429 )
		);
	}

	set_transient( $key, $counter + 1, $window * MINUTE_IN_SECONDS );

	return true;
}

/**
 * Build Overpass query.
 */
function wpli_osm_build_overpass_query( $type, $lat, $lng, $radius, $limit ) {
	$filters = array(
		'school'      => array('amenity' => 'school'),
		'supermarket' => array('shop' => 'supermarket'),
		'park'        => array('leisure' => 'park'),
		'hospital'    => array('amenity' => 'hospital'),
		'restaurant'  => array('amenity' => 'restaurant'),
	);

	$tags = $filters[ $type ];
	$tag_query = '';

	foreach ( $tags as $key => $value ) {
		$tag_query .= sprintf( '["%s"="%s"]', $key, $value );
	}

	$query = sprintf(
		'[out:json][timeout:25];(node%s(around:%d,%f,%f);way%s(around:%d,%f,%f);relation%s(around:%d,%f,%f););out center tags %d;',
		$tag_query,
		$radius,
		$lat,
		$lng,
		$tag_query,
		$radius,
		$lat,
		$lng,
		$tag_query,
		$radius,
		$lat,
		$lng,
		$limit
	);

	return $query;
}

/**
 * Fetch Overpass data with fallback endpoints.
 */
function wpli_osm_fetch_overpass_data( $query, $settings ) {
	$endpoints = array();
	if ( ! empty( $settings['overpass_endpoint'] ) ) {
		$endpoints[] = $settings['overpass_endpoint'];
	}

	if ( ! empty( $settings['fallback_endpoints'] ) ) {
		$fallbacks = preg_split( '/\r\n|\r|\n/', $settings['fallback_endpoints'] );
		$fallbacks = array_filter( array_map( 'trim', $fallbacks ) );
		$endpoints = array_merge( $endpoints, $fallbacks );
	}

	$site_url = home_url();
	$headers  = array(
		'User-Agent' => 'WP Location Insights (OSM) - ' . $site_url,
		'Accept'     => 'application/json',
	);

	foreach ( $endpoints as $endpoint ) {
		$response = wp_remote_post(
			esc_url_raw( $endpoint ),
			array(
				'timeout' => 20,
				'headers' => $headers,
				'body'    => array( 'data' => $query ),
			)
		);

		if ( is_wp_error( $response ) ) {
			continue;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( 200 !== $code ) {
			continue;
		}

		$data = json_decode( $body, true );
		if ( null === $data || ! isset( $data['elements'] ) ) {
			continue;
		}

		return $data['elements'];
	}

	return new WP_Error(
		'wpli_overpass_failed',
		__( 'Không thể tải dữ liệu từ Overpass. Vui lòng thử lại sau.', 'wp-location-insights-osm' ),
		array( 'status' => 502 )
	);
}

/**
 * Parse Overpass elements.
 */
function wpli_osm_parse_overpass_elements( $elements ) {
	$items = array();

	foreach ( $elements as $element ) {
		if ( ! isset( $element['type'], $element['id'] ) ) {
			continue;
		}

		$lat = null;
		$lng = null;

		if ( 'node' === $element['type'] && isset( $element['lat'], $element['lon'] ) ) {
			$lat = (float) $element['lat'];
			$lng = (float) $element['lon'];
		}

		if ( in_array( $element['type'], array( 'way', 'relation' ), true ) && isset( $element['center']['lat'], $element['center']['lon'] ) ) {
			$lat = (float) $element['center']['lat'];
			$lng = (float) $element['center']['lon'];
		}

		if ( null === $lat || null === $lng ) {
			continue;
		}

		$tags    = isset( $element['tags'] ) ? $element['tags'] : array();
		$name    = isset( $tags['name'] ) ? $tags['name'] : __( '(Không có tên)', 'wp-location-insights-osm' );
		$address = wpli_osm_build_address( $tags );
		$osm_url = sprintf( 'https://www.openstreetmap.org/%s/%d', $element['type'], $element['id'] );

		$items[] = array(
			'id'      => $element['id'],
			'name'    => $name,
			'lat'     => $lat,
			'lng'     => $lng,
			'address' => $address,
			'osm_url' => $osm_url,
		);
	}

	return $items;
}

/**
 * Build address string from tags.
 */
function wpli_osm_build_address( $tags ) {
	$parts = array();
	$keys  = array( 'addr:housenumber', 'addr:street', 'addr:suburb', 'addr:city', 'addr:province' );

	foreach ( $keys as $key ) {
		if ( ! empty( $tags[ $key ] ) ) {
			$parts[] = $tags[ $key ];
		}
	}

	return implode( ', ', $parts );
}
