(function () {
	function copyText(text) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text);
			return;
		}
		const textarea = document.createElement('textarea');
		textarea.value = text;
		document.body.appendChild(textarea);
		textarea.select();
		document.execCommand('copy');
		document.body.removeChild(textarea);
	}

	function initCopyButtons() {
		document.querySelectorAll('.wpli-copy-shortcode').forEach((button) => {
			button.addEventListener('click', () => {
				copyText(button.dataset.shortcode || '');
				button.textContent = 'Đã copy';
				setTimeout(() => {
					button.textContent = 'Copy';
				}, 1200);
			});
		});
	}

	function initShortcodeGenerator() {
		const btn = document.getElementById('wpli_generate_shortcode');
		if (!btn) {
			return;
		}
		btn.addEventListener('click', () => {
			const title = document.getElementById('wpli_sc_title').value.trim();
			const lat = document.getElementById('wpli_sc_lat').value.trim();
			const lng = document.getElementById('wpli_sc_lng').value.trim();
			const radius = document.getElementById('wpli_sc_radius').value.trim();
			const zoom = document.getElementById('wpli_sc_zoom').value.trim();
			const height = document.getElementById('wpli_sc_height').value.trim();
			const tab = document.getElementById('wpli_sc_tab').value;
			const locationId = document.getElementById('wpli_sc_location').value.trim();

			let shortcode = '[wpli_location_insights';
			if (locationId) {
				shortcode += ` location_id="${locationId}"`;
			} else {
				if (title) {
					shortcode += ` title="${title}"`;
				}
				if (lat) {
					shortcode += ` lat="${lat}"`;
				}
				if (lng) {
					shortcode += ` lng="${lng}"`;
				}
				if (radius) {
					shortcode += ` radius="${radius}"`;
				}
				if (zoom) {
					shortcode += ` zoom="${zoom}"`;
				}
				if (height) {
					shortcode += ` height="${height}"`;
				}
				if (tab) {
					shortcode += ` default_tab="${tab}"`;
				}
			}
			shortcode += ']';

			const output = document.getElementById('wpli_shortcode_output');
			output.value = shortcode;
			copyText(shortcode);
		});
	}

	function initAdminMap() {
		const mapEl = document.getElementById('wpli-admin-map');
		if (!mapEl || typeof window.L === 'undefined' || typeof window.wpliAdmin === 'undefined') {
			return;
		}
		const latInput = document.getElementById('wpli_lat');
		const lngInput = document.getElementById('wpli_lng');
		const initLat = parseFloat(mapEl.dataset.lat) || 10.762622;
		const initLng = parseFloat(mapEl.dataset.lng) || 106.660172;
		const map = L.map(mapEl).setView([initLat, initLng], window.wpliAdmin.defaultZoom || 15);
		L.tileLayer(window.wpliAdmin.tileUrl, {
			attribution: window.wpliAdmin.tileAttribution,
			maxZoom: 19
		}).addTo(map);

		const marker = L.marker([initLat, initLng], { draggable: true }).addTo(map);

		marker.on('moveend', (event) => {
			const position = event.target.getLatLng();
			latInput.value = position.lat.toFixed(6);
			lngInput.value = position.lng.toFixed(6);
		});

		map.on('click', (event) => {
			marker.setLatLng(event.latlng);
			latInput.value = event.latlng.lat.toFixed(6);
			lngInput.value = event.latlng.lng.toFixed(6);
		});
	}

	document.addEventListener('DOMContentLoaded', () => {
		initCopyButtons();
		initShortcodeGenerator();
		initAdminMap();
	});
})();
