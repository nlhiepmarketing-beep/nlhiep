=== WP Location Insights (OSM) ===
Contributors: openai
Tags: location, osm, leaflet, overpass
Requires at least: 5.8
Tested up to: 6.5
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A Location Insights block powered by OpenStreetMap and Overpass API with a shortcode for Flatsome UX Builder.

== Description ==
WP Location Insights (OSM) renders a map + tabs + list UI similar to batdongsan.com.vn Location Insights using a free stack (Leaflet + OSM + Overpass). It supports multiple instances per page and includes a settings page to customize labels, colors, markers, map behavior, and API caching.

== Installation ==
1. Upload the `wp-location-insights-osm` folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" menu.
3. Go to Settings → Location Insights (OSM) to configure defaults.
4. Add the shortcode in Flatsome UX Builder or any page builder:
   [wpli_location_insights title="La Pura" lat="10.915919" lng="106.713676" radius="2000" zoom="15" height="420" default_tab="school"]

== Frequently Asked Questions ==
= Does it use Google Maps? =
No. It uses Leaflet + OpenStreetMap tiles and Overpass API.

= Does it call Overpass directly from the browser? =
No. The plugin uses a WordPress REST endpoint and caches results with transients.

== Changelog ==
= 1.0.0 =
* Initial release.
