<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPLI_Shortcode' ) ) {
	class WPLI_Shortcode {
		private $enqueued = false;

		public function __construct() {
			add_action( 'init', array( $this, 'register_shortcode' ) );
			add_action( 'wp', array( $this, 'maybe_enqueue_assets' ) );
		}

		public function register_shortcode() {
			add_shortcode( 'wpli_location_insights', array( $this, 'render_shortcode' ) );
		}

		public function maybe_enqueue_assets() {
			if ( is_admin() ) {
				return;
			}

			$settings = wpli_get_settings();
			if ( ! empty( $settings['always_enqueue'] ) ) {
				$this->enqueue_assets();
				return;
			}

			if ( is_singular() ) {
				global $post;
				if ( $post && ( has_shortcode( $post->post_content, 'wpli_location_insights' ) || has_shortcode( $post->post_excerpt, 'wpli_location_insights' ) ) ) {
					$this->enqueue_assets();
				}
			}
		}

		private function enqueue_assets() {
			if ( $this->enqueued ) {
				return;
			}
			$this->enqueued = true;

			$settings = wpli_get_settings();
			if ( ! empty( $settings['load_leaflet'] ) ) {
				wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
				wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
			}
			if ( ! empty( $settings['load_fontawesome'] ) ) {
				wp_enqueue_style( 'fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', array(), '6.5.1' );
			}

			wp_enqueue_style( 'wpli-frontend', WPLI_PLUGIN_URL . 'assets/css/wpli-frontend.css', array(), WPLI_VERSION );
			wp_enqueue_script( 'wpli-frontend', WPLI_PLUGIN_URL . 'assets/js/wpli-frontend.js', array(), WPLI_VERSION, true );

			wp_localize_script(
				'wpli-frontend',
				'wpliSettings',
				array(
					'restUrl'   => esc_url_raw( rest_url( 'wpli/v1/poi' ) ),
					'nonce'     => wp_create_nonce( 'wp_rest' ),
					'tileUrl'   => $settings['tile_url'],
					'tileAttribution' => $settings['tile_attribution'],
					'tileUrlFallback' => $settings['tile_url_fallback'],
					'tileAttributionFallback' => $settings['tile_attribution_fallback'],
					'speedKmh'  => (int) $settings['speed_kmh'],
					'rounding'  => $settings['rounding'],
					'defaultMaxResults' => (int) $settings['default_max_results'],
					'accentColor' => $settings['accent_color'],
					'markerColor' => $settings['marker_color'],
					'labels'    => array(
						'school'      => $settings['label_school'],
						'supermarket' => $settings['label_supermarket'],
						'park'        => $settings['label_park'],
						'hospital'    => $settings['label_hospital'],
						'restaurant'  => $settings['label_restaurant'],
						'my_location' => $settings['label_my_location'],
					),
					'icons'     => array(
						'school'      => $settings['icon_school'],
						'supermarket' => $settings['icon_supermarket'],
						'park'        => $settings['icon_park'],
						'hospital'    => $settings['icon_hospital'],
						'restaurant'  => $settings['icon_restaurant'],
						'my_location' => $settings['icon_my_location'],
						'motorcycle'  => $settings['icon_motorcycle'],
					),
				)
			);
		}

		public function render_shortcode( $atts ) {
			$settings = wpli_get_settings();

			$atts = shortcode_atts(
				array(
					'location_id' => 0,
					'title'       => '',
					'lat'         => '',
					'lng'         => '',
					'radius'      => '',
					'zoom'        => '',
					'height'      => '',
					'default_tab' => '',
				),
				$atts,
				'wpli_location_insights'
			);

			$location_id = absint( $atts['location_id'] );
			$title       = sanitize_text_field( $atts['title'] );
			$lat         = $atts['lat'];
			$lng         = $atts['lng'];
			$radius      = $atts['radius'];
			$zoom        = $atts['zoom'];
			$height      = $atts['height'];
			$default_tab = sanitize_text_field( $atts['default_tab'] );

			if ( $location_id ) {
				$post = get_post( $location_id );
				if ( $post && 'wpli_location' === $post->post_type ) {
					if ( empty( $title ) ) {
						$title = $post->post_title;
					}
					$lat         = get_post_meta( $location_id, '_wpli_lat', true );
					$lng         = get_post_meta( $location_id, '_wpli_lng', true );
					$radius      = get_post_meta( $location_id, '_wpli_radius', true );
					$zoom        = get_post_meta( $location_id, '_wpli_zoom', true );
					$height      = get_post_meta( $location_id, '_wpli_height', true );
					$default_tab = get_post_meta( $location_id, '_wpli_default_tab', true );
				}
			}

			$lat = is_numeric( $lat ) ? (float) $lat : null;
			$lng = is_numeric( $lng ) ? (float) $lng : null;

			$radius = $radius !== '' ? absint( $radius ) : (int) $settings['default_radius'];
			$zoom   = $zoom !== '' ? absint( $zoom ) : (int) $settings['default_zoom'];
			$height = $height !== '' ? absint( $height ) : (int) $settings['default_height'];
			$default_tab = $default_tab ? $default_tab : 'school';

			$tabs = array( 'school', 'supermarket', 'park', 'hospital', 'restaurant', 'my_location' );
			if ( ! in_array( $default_tab, $tabs, true ) ) {
				$default_tab = 'school';
			}

			$this->enqueue_assets();

			static $instance = 0;
			$instance++;
			$container_id = 'wpli-' . $instance;

			$wrapper_styles = sprintf( 'style="--wpli-accent:%s;--wpli-marker:%s;"', esc_attr( $settings['accent_color'] ), esc_attr( $settings['marker_color'] ) );

			ob_start();
			?>
			<div class="wpli" id="<?php echo esc_attr( $container_id ); ?>" <?php echo $wrapper_styles; ?>
				data-wpli-id="<?php echo esc_attr( $instance ); ?>"
				data-lat="<?php echo esc_attr( $lat ); ?>"
				data-lng="<?php echo esc_attr( $lng ); ?>"
				data-radius="<?php echo esc_attr( $radius ); ?>"
				data-zoom="<?php echo esc_attr( $zoom ); ?>"
				data-height="<?php echo esc_attr( $height ); ?>"
				data-default-tab="<?php echo esc_attr( $default_tab ); ?>"
			>
				<?php if ( ! empty( $title ) ) : ?>
					<h3 class="wpli-title"><?php echo esc_html( $title ); ?></h3>
				<?php endif; ?>
				<div class="wpli-map" style="height: <?php echo esc_attr( $height ); ?>px;">
					<div class="wpli-loading">Đang tải bản đồ...</div>
				</div>
				<div class="wpli-panel">
					<div class="wpli-tabs" role="tablist">
						<?php foreach ( $tabs as $tab_key ) : ?>
							<button type="button" class="wpli-tab<?php echo ( $tab_key === $default_tab ) ? ' active' : ''; ?>" data-type="<?php echo esc_attr( $tab_key ); ?>">
								<i class="<?php echo esc_attr( $settings[ 'icon_' . $tab_key ] ); ?>"></i>
								<span class="wpli-tab-label"><?php echo esc_html( $settings[ 'label_' . $tab_key ] ); ?></span>
							</button>
						<?php endforeach; ?>
					</div>
					<div class="wpli-summary">Đang tải dữ liệu...</div>
					<div class="wpli-list" role="list">
						<div class="wpli-loading">Đang tải...</div>
					</div>
				</div>
				<?php if ( null === $lat || null === $lng ) : ?>
					<div class="wpli-error">
						<?php
						if ( current_user_can( 'manage_options' ) ) {
							esc_html_e( 'Thiếu toạ độ hợp lệ. Vui lòng kiểm tra shortcode hoặc vị trí.', 'wp-location-insights-osm' );
						} else {
							esc_html_e( 'Không thể hiển thị bản đồ ở thời điểm này.', 'wp-location-insights-osm' );
						}
						?>
					</div>
				<?php endif; ?>
			</div>
			<?php
			return ob_get_clean();
		}
	}
}

if ( class_exists( 'WPLI_Shortcode' ) ) {
	new WPLI_Shortcode();
}
