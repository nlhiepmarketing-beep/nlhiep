=== WP Location Insights (OSM) ===
Contributors: openai
Tags: maps, leaflet, osm, overpass, location
Requires at least: 5.8
Tested up to: 6.5
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

WP Location Insights (OSM) hiển thị bản đồ Leaflet + OpenStreetMap với danh sách địa điểm lân cận dựa trên Overpass API, mô phỏng UI “Location Insights”.

== Installation ==

1. Upload thư mục plugin `wp-location-insights-osm` vào `/wp-content/plugins/`.
2. Kích hoạt plugin trong WordPress Admin.
3. Vào Location Insights > Cài đặt để cấu hình mặc định.
4. Tạo vị trí mới trong Location Insights > Vị trí hoặc dùng shortcode trực tiếp.

== Usage ==

Shortcode mẫu:

[wpli_location_insights title="Đầm sen" lat="10.915919" lng="106.713676" radius="5000" zoom="15" height="420" default_tab="school"]

Hoặc dùng location_id:

[wpli_location_insights location_id="123"]

== OpenStreetMap Attribution ==

Plugin sử dụng OpenStreetMap tiles và Overpass API. Vui lòng giữ attribution “© OpenStreetMap contributors” theo yêu cầu của OSM.

== Caching ==

Dữ liệu POI được cache bằng transients. Có thể chỉnh TTL trong Cài đặt để giảm tải cho Overpass API.
