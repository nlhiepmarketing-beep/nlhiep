(() => {
	const instances = document.querySelectorAll('.wpli-wrapper');
	if (!instances.length) {
		return;
	}

	const formatDistance = (meters) => {
		if (meters >= 1000) {
			const km = meters / 1000;
			return `${new Intl.NumberFormat('vi-VN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(km)} km`;
		}
		return `${new Intl.NumberFormat('vi-VN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(meters)} m`;
	};

	const haversine = (lat1, lon1, lat2, lon2) => {
		const toRad = (val) => (val * Math.PI) / 180;
		const R = 6371000;
		const dLat = toRad(lat2 - lat1);
		const dLon = toRad(lon2 - lon1);
		const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
			Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
		const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
		return R * c;
	};

	const calcMinutes = (meters, speedKmh, rounding) => {
		const speedMs = (speedKmh * 1000) / 3600;
		const minutes = meters / speedMs / 60;
		if (rounding === 'ceil') {
			return Math.ceil(minutes);
		}
		if (rounding === 'round') {
			return Math.round(minutes);
		}
		return Math.floor(minutes);
	};

	const createIcon = (iconClass, color, isProject = false) => {
		const className = isProject ? 'wpli-marker wpli-marker-project' : 'wpli-marker';
		const html = `<div class="${className}" style="--wpli-marker-color:${color}"><i class="${iconClass}"></i></div>`;
		return L.divIcon({
			html,
			className: 'wpli-div-icon',
			iconSize: [28, 28],
			iconAnchor: [14, 28],
			popupAnchor: [0, -24]
		});
	};

	instances.forEach((wrapper) => {
		const mapEl = wrapper.querySelector('.wpli-map');
		const tabsEl = wrapper.querySelector('.wpli-tabs');
		const summaryEl = wrapper.querySelector('.wpli-summary');
		const listEl = wrapper.querySelector('.wpli-list');
		const statusEl = wrapper.querySelector('.wpli-status');

		const lat = parseFloat(wrapper.dataset.lat);
		const lng = parseFloat(wrapper.dataset.lng);
		const radius = parseInt(wrapper.dataset.radius, 10);
		const zoom = parseInt(wrapper.dataset.zoom, 10);
		const height = parseInt(wrapper.dataset.height, 10);
		const defaultTab = wrapper.dataset.defaultTab || 'school';
		const tileUrl = wrapper.dataset.tileUrl;
		const tileAttribution = wrapper.dataset.tileAttribution;
		const title = wrapper.dataset.title || '';

		const labels = WPLIOSM.labels || {};
		const icons = WPLIOSM.icons || {};
		const settings = WPLIOSM.settings || {};

		wrapper.style.setProperty('--wpli-accent-color', settings.accentColor || '#d32f2f');

		mapEl.style.height = `${height}px`;

		const map = L.map(mapEl, {
			center: [lat, lng],
			zoom,
			scrollWheelZoom: false
		});

		L.tileLayer(tileUrl, {
			attribution: tileAttribution
		}).addTo(map);

		const projectIcon = createIcon(icons.my_location || 'fa-solid fa-location-dot', settings.markerColor || '#2aa8a8', true);
		const projectMarker = L.marker([lat, lng], { icon: projectIcon }).addTo(map);
		if (title) {
			projectMarker.bindPopup(`<div class="wpli-popup"><h4>${title}</h4></div>`);
		}

		const state = {
			markers: [],
			listItems: new Map(),
			activeTab: null,
			myLocationMarker: null,
			routeLine: null
		};

		const tabs = [
			{ key: 'school', label: labels.school, icon: icons.school },
			{ key: 'supermarket', label: labels.supermarket, icon: icons.supermarket },
			{ key: 'park', label: labels.park, icon: icons.park },
			{ key: 'hospital', label: labels.hospital, icon: icons.hospital },
			{ key: 'restaurant', label: labels.restaurant, icon: icons.restaurant },
			{ key: 'my_location', label: labels.my_location, icon: icons.my_location }
		];

		const setStatus = (message) => {
			statusEl.textContent = message || '';
		};

		const clearRouteLine = () => {
			if (state.routeLine) {
				state.routeLine.remove();
				state.routeLine = null;
			}
		};

		const drawRouteLine = (targetLatLng) => {
			clearRouteLine();
			state.routeLine = L.polyline([[lat, lng], targetLatLng], {
				color: settings.accentColor || '#d32f2f',
				weight: 3,
				opacity: 0.8,
				dashArray: '6 6'
			}).addTo(map);
		};

		const clearResults = () => {
			state.markers.forEach((marker) => marker.remove());
			state.markers = [];
			state.listItems.clear();
			listEl.innerHTML = '';
		};

		const clearMyLocation = () => {
			if (state.myLocationMarker) {
				state.myLocationMarker.remove();
				state.myLocationMarker = null;
			}
		};

		const setActiveTab = (key) => {
			state.activeTab = key;
			const tabNodes = tabsEl.querySelectorAll('.wpli-tab');
			tabNodes.forEach((node) => {
				const isActive = node.dataset.tab === key;
				node.classList.toggle('active', isActive);
				node.style.borderBottomColor = isActive ? (settings.accentColor || '#d32f2f') : 'transparent';
			});
		};

		const focusListItem = (id) => {
			const item = state.listItems.get(id);
			if (!item) {
				return;
			}
			listEl.querySelectorAll('.wpli-list-item').forEach((node) => node.classList.remove('active'));
			item.classList.add('active');
			item.scrollIntoView({ block: 'nearest' });
		};

		const renderList = (items) => {
			listEl.innerHTML = '';
			state.listItems.clear();

			items.forEach((item) => {
				const distance = haversine(lat, lng, item.lat, item.lng);
				const minutes = calcMinutes(distance, settings.speedKmh || 25, settings.rounding || 'floor');
				const row = document.createElement('div');
				row.className = 'wpli-list-item';
				row.setAttribute('role', 'listitem');
				row.innerHTML = `
					<div class="wpli-item-left">
						<div class="wpli-item-name">${item.name}</div>
						<div class="wpli-item-address">${item.address || ''}</div>
					</div>
					<div class="wpli-item-right">
						<div class="wpli-item-distance">${formatDistance(distance)}</div>
						<div class="wpli-item-time"><i class="${settings.motorcycleIcon}"></i> ${minutes} phút</div>
					</div>
				`;
				row.addEventListener('click', () => {
					const marker = state.markers.find((m) => m.options.wpliId === item.id);
					if (marker) {
						marker.openPopup();
						map.setView(marker.getLatLng(), Math.max(map.getZoom(), zoom));
						drawRouteLine(marker.getLatLng());
						focusListItem(item.id);
					}
				});
				state.listItems.set(item.id, row);
				listEl.appendChild(row);
			});
		};

		const renderMarkers = (items, iconClass) => {
			state.markers = items.map((item) => {
				const marker = L.marker([item.lat, item.lng], {
					icon: createIcon(iconClass, settings.markerColor || '#2aa8a8'),
					wpliId: item.id
				}).addTo(map);
				const popupHtml = `<div class="wpli-popup"><h4>${item.name}</h4>${item.address ? `<p>${item.address}</p>` : ''}</div>`;
				marker.bindPopup(popupHtml);
				marker.on('click', () => {
					focusListItem(item.id);
					drawRouteLine(marker.getLatLng());
				});
				return marker;
			});
		};

		const renderSummary = (count, label) => {
			const km = radius / 1000;
			const kmText = new Intl.NumberFormat('vi-VN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(km);
			summaryEl.textContent = `Có ${count} ${label} trong vòng ${kmText} km`;
		};

		const loadPois = async (key) => {
			clearResults();
			clearMyLocation();
			clearRouteLine();
			setStatus('Đang tải dữ liệu...');

			const params = new URLSearchParams({
				lat: lat.toString(),
				lng: lng.toString(),
				radius: radius.toString(),
				limit: (WPLIOSM.settings && WPLIOSM.settings.maxResults) ? WPLIOSM.settings.maxResults.toString() : '',
				type: key
			});

			try {
				const response = await fetch(`${WPLIOSM.restUrl}?${params.toString()}`, {
					headers: {
						'X-WP-Nonce': WPLIOSM.nonce
					}
				});

				if (!response.ok) {
					throw new Error('Request failed');
				}

				const data = await response.json();
				setStatus('');
				renderSummary(data.count, labels[key]);
				renderList(data.items);
				renderMarkers(data.items, icons[key]);
			} catch (error) {
				setStatus('Không thể tải dữ liệu. Vui lòng thử lại.');
				summaryEl.textContent = '';
			}
		};

		const loadMyLocation = () => {
			clearResults();
			clearRouteLine();
			setStatus('Đang lấy vị trí của bạn...');
			summaryEl.textContent = '';
			if (!navigator.geolocation) {
				setStatus('Trình duyệt không hỗ trợ định vị.');
				return;
			}

			navigator.geolocation.getCurrentPosition(
				(position) => {
					const userLat = position.coords.latitude;
					const userLng = position.coords.longitude;
					clearMyLocation();
					state.myLocationMarker = L.marker([userLat, userLng], {
						icon: createIcon(icons.my_location, settings.markerColor || '#2aa8a8')
					}).addTo(map);
					map.setView([userLat, userLng], Math.max(map.getZoom(), zoom));
					drawRouteLine([userLat, userLng]);
					const distance = haversine(userLat, userLng, lat, lng);
					const km = distance / 1000;
					const kmText = new Intl.NumberFormat('vi-VN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(km);
					summaryEl.textContent = `Bạn cách dự án khoảng ${kmText} km`;
					setStatus('');
				},
				() => {
					setStatus('Không thể lấy vị trí của bạn. Vui lòng kiểm tra quyền truy cập.');
				}
			);
		};

		const initTabs = () => {
			tabsEl.innerHTML = '';
			tabs.forEach((tab) => {
				const btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'wpli-tab';
				btn.dataset.tab = tab.key;
				btn.innerHTML = `<i class="${tab.icon}"></i><span>${tab.label}</span>`;
				btn.addEventListener('click', () => {
					setActiveTab(tab.key);
					if (tab.key === 'my_location') {
						loadMyLocation();
						return;
					}
					loadPois(tab.key);
				});
				tabsEl.appendChild(btn);
			});
		};

		initTabs();
		setActiveTab(defaultTab);
		if (defaultTab === 'my_location') {
			loadMyLocation();
		} else {
			loadPois(defaultTab);
		}
	});
})();
