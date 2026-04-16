import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;
window.Pusher = Pusher;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

if (reverbKey) {
	const configuredHost = import.meta.env.VITE_REVERB_HOST;
	const hasConfiguredHost = Boolean(configuredHost);
	const browserHost = window.location.hostname;
	const isLocalHost = browserHost === 'localhost'
		|| browserHost === '127.0.0.1'
		|| browserHost === '::1'
		|| browserHost.endsWith('.test');
	const isConfiguredLocalHost = !hasConfiguredHost
		|| configuredHost === 'localhost'
		|| configuredHost === '127.0.0.1'
		|| configuredHost === '::1'
		|| configuredHost.endsWith('.test');
	const shouldUseBrowserHost = !hasConfiguredHost || isConfiguredLocalHost;
	const isPublicShareHost = shouldUseBrowserHost && !isLocalHost;

	if (isPublicShareHost && !hasConfiguredHost) {
		// Herd Share exposes the web app URL, not the Reverb port.
		// Configure VITE_REVERB_HOST with a second shared URL for :8080 to enable real-time updates.
		return;
	}

	const isConfiguredPublicHost = hasConfiguredHost && !isConfiguredLocalHost;
	const configuredScheme = import.meta.env.VITE_REVERB_SCHEME;
	const forceTLS = isPublicShareHost
		|| isConfiguredPublicHost
		|| (configuredScheme ? configuredScheme === 'https' : window.location.protocol === 'https:');
	const wsPort = Number(import.meta.env.VITE_REVERB_PORT || 8080);
	const wssPort = Number(import.meta.env.VITE_REVERB_WSS_PORT || 443);

	window.Echo = new Echo({
		broadcaster: 'reverb',
		key: reverbKey,
		wsHost: shouldUseBrowserHost ? browserHost : configuredHost,
		wsPort: forceTLS ? wssPort : wsPort,
		wssPort,
		forceTLS,
		enabledTransports: ['ws', 'wss'],
	});
}
