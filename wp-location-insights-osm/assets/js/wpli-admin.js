(() => {
	const title = document.getElementById('wpli_sc_title');
	const lat = document.getElementById('wpli_sc_lat');
	const lng = document.getElementById('wpli_sc_lng');
	const radius = document.getElementById('wpli_sc_radius');
	const zoom = document.getElementById('wpli_sc_zoom');
	const output = document.getElementById('wpli_sc_output');

	if (!output) {
		return;
	}

	const buildShortcode = () => {
		const attrs = [];
		if (title.value.trim()) {
			attrs.push(`title="${title.value.trim()}"`);
		}
		if (lat.value.trim()) {
			attrs.push(`lat="${lat.value.trim()}"`);
		}
		if (lng.value.trim()) {
			attrs.push(`lng="${lng.value.trim()}"`);
		}
		if (radius.value.trim()) {
			attrs.push(`radius="${radius.value.trim()}"`);
		}
		if (zoom.value.trim()) {
			attrs.push(`zoom="${zoom.value.trim()}"`);
		}
		output.value = `[wpli_location_insights ${attrs.join(' ')}]`.trim();
	};

	[title, lat, lng, radius, zoom].forEach((input) => {
		if (!input) {
			return;
		}
		input.addEventListener('input', buildShortcode);
	});

	buildShortcode();
})();
