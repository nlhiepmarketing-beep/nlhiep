(function () {
	if (typeof window.wpliSettings === 'undefined') {
		return;
	}

	const SETTINGS = window.wpliSettings;
	const instances = [];

	const TAB_TYPES = [
		'school',
		'supermarket',
		'park',
		'hospital',
		'restaurant',
		'my_location'
	];

	const localeFormatter = new Intl.NumberFormat('vi-VN', {
		minimumFractionDigits: 2,
		maximumFractionDigits: 2
	});

	function formatDistance(meters) {
		if (meters < 1000) {
			return `${localeFormatter.format(meters)} m`;
		}
		return `${localeFormatter.format(meters / 1000)} km`;
	}

	function estimateMinutes(distanceMeters) {
		const speedMps = (SETTINGS.speedKmh || 25) * 1000 / 3600;
		const minutes = distanceMeters / speedMps / 60;
		const rounding = SETTINGS.rounding || 'floor';
		if (rounding === 'ceil') {
			return Math.ceil(minutes);
		}
		if (rounding === 'round') {
			return Math.round(minutes);
		}
		return Math.floor(minutes);
	}

	function createMarkerIcon(iconClass, isProject) {
		const className = isProject ? 'wpli-marker-project' : 'wpli-marker';
		const html = `<div class="${className}"><i class="${iconClass}"></i></div>`;
		return L.divIcon({
			html,
			className: '',
			iconSize: [34, 34],
			iconAnchor: [17, 34],
			popupAnchor: [0, -30]
		});
	}

	function WPLIInstance(container) {
		this.container = container;
		this.mapElement = container.querySelector('.wpli-map');
		this.tabsElement = container.querySelector('.wpli-tabs');
		this.summaryElement = container.querySelector('.wpli-summary');
		this.listElement = container.querySelector('.wpli-list');

		this.lat = parseFloat(container.dataset.lat);
		this.lng = parseFloat(container.dataset.lng);
		this.radius = parseInt(container.dataset.radius, 10);
		this.zoom = parseInt(container.dataset.zoom, 10);
		this.height = parseInt(container.dataset.height, 10);
		this.defaultTab = container.dataset.defaultTab;

		this.map = null;
		this.projectMarker = null;
		this.markers = [];
		this.activeTab = this.defaultTab;
		this.tileLayer = null;
		this.tileErrorTimes = [];
		this.myLocationMarker = null;

		this.init();
	}

	WPLIInstance.prototype.init = function () {
		this.renderTabs();
		this.initMap();
		this.loadTab(this.activeTab);
	};

	WPLIInstance.prototype.renderTabs = function () {
		this.tabsElement.innerHTML = '';
		TAB_TYPES.forEach((type) => {
			const tab = document.createElement('button');
			tab.type = 'button';
			tab.className = 'wpli-tab';
			tab.dataset.type = type;
			tab.innerHTML = `<i class="${SETTINGS.icons[type]}"></i><span>${SETTINGS.labels[type]}</span>`;
			if (type === this.activeTab) {
				tab.classList.add('active');
			}
			tab.addEventListener('click', () => {
				if (this.activeTab === type) {
					return;
				}
				this.activeTab = type;
				this.tabsElement.querySelectorAll('.wpli-tab').forEach((item) => {
					item.classList.toggle('active', item.dataset.type === type);
				});
				this.loadTab(type);
			});
			this.tabsElement.appendChild(tab);
		});
	};

	WPLIInstance.prototype.initMap = function () {
		this.map = L.map(this.mapElement, {
			zoomControl: true,
			scrollWheelZoom: false
		}).setView([this.lat, this.lng], this.zoom);

		this.tileLayer = L.tileLayer(SETTINGS.tileUrl, {
			attribution: SETTINGS.tileAttribution,
			maxZoom: 19
		});
		this.tileLayer.addTo(this.map);

		this.tileLayer.on('tileerror', () => {
			const now = Date.now();
			this.tileErrorTimes = this.tileErrorTimes.filter((time) => now - time < 2000);
			this.tileErrorTimes.push(now);
			if (this.tileErrorTimes.length >= 3) {
				this.showMapMessage('Không tải được nền bản đồ. Đang thử nguồn khác…');
				if (SETTINGS.tileUrlFallback) {
					this.tileLayer.setUrl(SETTINGS.tileUrlFallback);
					this.tileLayer.options.attribution = SETTINGS.tileAttributionFallback || '';
				}
			}
		});

		this.projectMarker = L.marker([this.lat, this.lng], {
			icon: createMarkerIcon('fa-solid fa-location-dot', true)
		}).addTo(this.map);

		setTimeout(() => this.map.invalidateSize(), 300);
		setTimeout(() => this.map.invalidateSize(), 1200);

		const resizeObserver = new ResizeObserver(() => {
			this.map.invalidateSize();
		});
		resizeObserver.observe(this.mapElement);

		window.addEventListener('resize', () => {
			this.map.invalidateSize();
		});
	};

	WPLIInstance.prototype.showMapMessage = function (message) {
		let messageEl = this.mapElement.querySelector('.wpli-map-message');
		if (!messageEl) {
			messageEl = document.createElement('div');
			messageEl.className = 'wpli-map-message';
			this.mapElement.appendChild(messageEl);
		}
		messageEl.textContent = message;
	};

	WPLIInstance.prototype.clearMarkers = function () {
		this.markers.forEach((marker) => this.map.removeLayer(marker));
		this.markers = [];
		if (this.myLocationMarker) {
			this.map.removeLayer(this.myLocationMarker);
			this.myLocationMarker = null;
		}
	};

	WPLIInstance.prototype.renderLoading = function () {
		this.summaryElement.textContent = '';
		this.listElement.innerHTML = '<div class="wpli-loading">Đang tải dữ liệu...</div>';
	};

	WPLIInstance.prototype.renderMessage = function (message) {
		this.listElement.innerHTML = `<div class="wpli-message">${message}</div>`;
	};

	WPLIInstance.prototype.setActiveListItem = function (index) {
		this.listElement.querySelectorAll('.wpli-item').forEach((item) => {
			item.classList.toggle('active', parseInt(item.dataset.index, 10) === index);
		});
	};

	WPLIInstance.prototype.loadTab = function (type) {
		this.clearMarkers();
		this.renderLoading();

		if (type === 'my_location') {
			this.handleMyLocation();
			return;
		}

		const url = new URL(SETTINGS.restUrl);
		url.searchParams.set('lat', this.lat);
		url.searchParams.set('lng', this.lng);
		url.searchParams.set('radius', this.radius);
		url.searchParams.set('type', type);
		url.searchParams.set('limit', SETTINGS.defaultMaxResults || 20);

		fetch(url.toString(), {
			method: 'GET',
			headers: {
				'X-WP-Nonce': SETTINGS.nonce
			}
		})
			.then((response) => {
				if (!response.ok) {
					throw new Error('Request failed');
				}
				return response.json();
			})
			.then((data) => {
				this.renderPoi(type, data.items || []);
			})
			.catch(() => {
				this.renderMessage('Không thể tải dữ liệu. Vui lòng thử lại.');
			});
	};

	WPLIInstance.prototype.renderPoi = function (type, items) {
		this.listElement.innerHTML = '';

		if (!items.length) {
			this.summaryElement.textContent = 'Không tìm thấy kết quả phù hợp.';
			this.renderMessage('Không có địa điểm trong phạm vi này.');
			return;
		}

		this.summaryElement.textContent = `Có ${items.length} ${SETTINGS.labels[type]} trong vòng ${formatDistance(this.radius)}`;

		items.forEach((item, index) => {
			const distance = typeof item.distance_m === 'number'
				? item.distance_m
				: this.calculateDistance(this.lat, this.lng, item.lat, item.lng);
			const minutes = estimateMinutes(distance);

			const marker = L.marker([item.lat, item.lng], {
				icon: createMarkerIcon(SETTINGS.icons[type])
			}).addTo(this.map);

			marker.bindPopup(`<strong>${item.name}</strong><br>${item.address || ''}`);
			marker.on('click', () => {
				this.setActiveListItem(index);
			});

			this.markers.push(marker);

			const row = document.createElement('div');
			row.className = 'wpli-item';
			row.dataset.index = index;
			row.innerHTML = `
				<div>
					<div class="wpli-item-name">${item.name}</div>
					<div class="wpli-item-address">${item.address || ''}</div>
				</div>
				<div class="wpli-item-meta">
					<div>${formatDistance(distance)}</div>
					<div class="wpli-item-time"><i class="${SETTINGS.icons.motorcycle}"></i>${minutes} phút</div>
				</div>
			`;

			row.addEventListener('click', () => {
				this.map.setView([item.lat, item.lng], this.map.getZoom(), { animate: true });
				marker.openPopup();
				this.setActiveListItem(index);
			});

			this.listElement.appendChild(row);
		});
	};

	WPLIInstance.prototype.calculateDistance = function (lat1, lng1, lat2, lng2) {
		const R = 6371000;
		const dLat = (lat2 - lat1) * Math.PI / 180;
		const dLng = (lng2 - lng1) * Math.PI / 180;
		const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
			Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
			Math.sin(dLng / 2) * Math.sin(dLng / 2);
		const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
		return R * c;
	};

	WPLIInstance.prototype.handleMyLocation = function () {
		this.listElement.innerHTML = '';
		if (!navigator.geolocation) {
			this.summaryElement.textContent = 'Trình duyệt không hỗ trợ định vị.';
			this.renderMessage('Không thể lấy vị trí của bạn.');
			return;
		}

		navigator.geolocation.getCurrentPosition(
			(position) => {
				const lat = position.coords.latitude;
				const lng = position.coords.longitude;
				const distance = this.calculateDistance(this.lat, this.lng, lat, lng);
				const distanceText = formatDistance(distance);
				this.summaryElement.textContent = `Bạn cách dự án khoảng ${distanceText}`;

				this.myLocationMarker = L.marker([lat, lng], {
					icon: createMarkerIcon(SETTINGS.icons.my_location)
				}).addTo(this.map);
				this.map.setView([lat, lng], this.map.getZoom());
				this.renderMessage('Đã đánh dấu vị trí của bạn trên bản đồ.');
			},
			() => {
				this.summaryElement.textContent = 'Không thể truy cập vị trí của bạn.';
				this.renderMessage('Vui lòng bật quyền định vị trong trình duyệt.');
			}
		);
	};

	document.addEventListener('DOMContentLoaded', () => {
		document.querySelectorAll('.wpli').forEach((container) => {
			instances.push(new WPLIInstance(container));
		});
	});
})();
