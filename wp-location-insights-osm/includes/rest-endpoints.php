<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'rest_api_init', 'wpli_register_rest_routes' );

/**
 * Register REST routes.
 */
function wpli_register_rest_routes() {
	register_rest_route(
		'wpli/v1',
		'/poi',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'wpli_handle_poi_request',
			'permission_callback' => '__return_true',
			'args'                => array(
				'lat'    => array( 'required' => true ),
				'lng'    => array( 'required' => true ),
				'radius' => array( 'required' => true ),
				'type'   => array( 'required' => true ),
				'limit'  => array( 'required' => false ),
			),
		)
	);
}

/**
 * Handle POI request.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function wpli_handle_poi_request( WP_REST_Request $request ) {
	$params = $request->get_params();

	$lat = isset( $params['lat'] ) ? (float) $params['lat'] : null;
	$lng = isset( $params['lng'] ) ? (float) $params['lng'] : null;
	$radius = isset( $params['radius'] ) ? absint( $params['radius'] ) : 0;
	$type = isset( $params['type'] ) ? sanitize_text_field( $params['type'] ) : '';
	$limit = isset( $params['limit'] ) ? absint( $params['limit'] ) : 0;

	if ( $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 ) {
		return new WP_REST_Response( array( 'message' => 'Invalid coordinates.' ), 400 );
	}
	if ( $radius < 200 || $radius > 10000 ) {
		return new WP_REST_Response( array( 'message' => 'Invalid radius.' ), 400 );
	}
	if ( $limit < 1 || $limit > 50 ) {
		$settings = wpli_get_settings();
		$limit    = (int) $settings['default_max_results'];
	}

	$allowed_types = array( 'school', 'supermarket', 'park', 'hospital', 'restaurant' );
	if ( ! in_array( $type, $allowed_types, true ) ) {
		return new WP_REST_Response( array( 'message' => 'Invalid type.' ), 400 );
	}

	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	$rate_key = 'wpli_rate_' . md5( $ip );
	$rate = (int) get_transient( $rate_key );
	if ( $rate >= WPLI_RATE_LIMIT_MAX ) {
		return new WP_REST_Response( array( 'message' => 'Rate limit exceeded.' ), 429 );
	}
	set_transient( $rate_key, $rate + 1, WPLI_RATE_LIMIT_WINDOW );

	$cache_key = 'wpli_poi_' . md5( $lat . '|' . $lng . '|' . $radius . '|' . $type . '|' . $limit );
	$cached = get_transient( $cache_key );
	if ( false !== $cached ) {
		return new WP_REST_Response( $cached, 200 );
	}

	$settings = wpli_get_settings();
	$overpass_url = $settings['overpass_endpoint'];
	if ( empty( $overpass_url ) ) {
		return new WP_REST_Response( array( 'message' => 'Overpass endpoint missing.' ), 500 );
	}

	$filter = wpli_get_overpass_filter( $type );
	$query = "[out:json][timeout:25];\n(\n";
	$query .= "node(around:" . $radius . ',' . $lat . ',' . $lng . ')' . $filter . ";\n";
	$query .= "way(around:" . $radius . ',' . $lat . ',' . $lng . ')' . $filter . ";\n";
	$query .= "relation(around:" . $radius . ',' . $lat . ',' . $lng . ')' . $filter . ";\n";
	$query .= ");\nout center tags;";

	$response = wp_remote_post(
		$overpass_url,
		array(
			'timeout' => 30,
			'headers' => array(
				'User-Agent' => 'WP Location Insights OSM (' . home_url() . ')',
				'Content-Type' => 'application/x-www-form-urlencoded',
			),
			'body'    => 'data=' . rawurlencode( $query ),
		)
	);

	if ( is_wp_error( $response ) ) {
		return new WP_REST_Response( array( 'message' => $response->get_error_message() ), 500 );
	}

	$body = wp_remote_retrieve_body( $response );
	if ( empty( $body ) ) {
		return new WP_REST_Response( array( 'message' => 'Empty response from Overpass.' ), 500 );
	}

	$data = json_decode( $body, true );
	if ( empty( $data['elements'] ) ) {
		$result = array( 'count' => 0, 'items' => array() );
		set_transient( $cache_key, $result, $settings['cache_ttl'] * MINUTE_IN_SECONDS );
		return new WP_REST_Response( $result, 200 );
	}

	$items = array();
	foreach ( $data['elements'] as $element ) {
		if ( empty( $element['tags'] ) ) {
			continue;
		}
		$tags = $element['tags'];
		$name = ! empty( $tags['name'] ) ? $tags['name'] : __( '(Không có tên)', 'wp-location-insights-osm' );

		$lat_item = null;
		$lng_item = null;
		if ( 'node' === $element['type'] ) {
			$lat_item = isset( $element['lat'] ) ? (float) $element['lat'] : null;
			$lng_item = isset( $element['lon'] ) ? (float) $element['lon'] : null;
		} elseif ( isset( $element['center'] ) ) {
			$lat_item = isset( $element['center']['lat'] ) ? (float) $element['center']['lat'] : null;
			$lng_item = isset( $element['center']['lon'] ) ? (float) $element['center']['lon'] : null;
		}
		if ( null === $lat_item || null === $lng_item ) {
			continue;
		}

		$address_parts = array();
		foreach ( array( 'addr:housenumber', 'addr:street', 'addr:suburb', 'addr:city', 'addr:province' ) as $addr_key ) {
			if ( ! empty( $tags[ $addr_key ] ) ) {
				$address_parts[] = $tags[ $addr_key ];
			}
		}
		$address = ! empty( $address_parts ) ? implode( ', ', $address_parts ) : '';

		$distance = wpli_haversine_distance( $lat, $lng, $lat_item, $lng_item );

		$items[] = array(
			'id'         => $element['id'],
			'name'       => $name,
			'lat'        => $lat_item,
			'lng'        => $lng_item,
			'address'    => $address,
			'distance_m' => $distance,
			'osm_url'    => wpli_build_osm_url( $element['type'], $element['id'] ),
		);
	}

	usort(
		$items,
		function ( $a, $b ) {
			return $a['distance_m'] <=> $b['distance_m'];
		}
	);

	$items = array_slice( $items, 0, $limit );
	$result = array(
		'count' => count( $items ),
		'items' => $items,
	);

	set_transient( $cache_key, $result, $settings['cache_ttl'] * MINUTE_IN_SECONDS );
	return new WP_REST_Response( $result, 200 );
}

/**
 * Build Overpass filter.
 *
 * @param string $type Type.
 * @return string
 */
function wpli_get_overpass_filter( $type ) {
	switch ( $type ) {
		case 'school':
			return '["amenity"~"^(school|kindergarten|college|university)$"]';
		case 'supermarket':
			return '["shop"~"^(supermarket|convenience)$"]';
		case 'park':
			return '["leisure"="park"]';
		case 'hospital':
			return '["amenity"~"^(hospital|clinic|doctors|pharmacy)$"]';
		case 'restaurant':
		default:
			return '["amenity"~"^(restaurant|cafe|fast_food)$"]';
	}
}

/**
 * Haversine distance in meters.
 *
 * @param float $lat1 Lat1.
 * @param float $lon1 Lon1.
 * @param float $lat2 Lat2.
 * @param float $lon2 Lon2.
 * @return float
 */
function wpli_haversine_distance( $lat1, $lon1, $lat2, $lon2 ) {
	$earth_radius = 6371000;
	$dlat = deg2rad( $lat2 - $lat1 );
	$dlon = deg2rad( $lon2 - $lon1 );
	$a = sin( $dlat / 2 ) * sin( $dlat / 2 ) + cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * sin( $dlon / 2 ) * sin( $dlon / 2 );
	$c = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
	return $earth_radius * $c;
}

/**
 * Build OSM URL.
 *
 * @param string $type Type.
 * @param int    $id ID.
 * @return string
 */
function wpli_build_osm_url( $type, $id ) {
	if ( empty( $type ) || empty( $id ) ) {
		return '';
	}
	return esc_url_raw( 'https://www.openstreetmap.org/' . $type . '/' . $id );
}
