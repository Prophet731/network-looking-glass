/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
    wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
    wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
    wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
    encrypted: true,
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
});

window.Echo.connector.pusher.connection.bind('connecting', (payload) => {
    /**
     * All dependencies have been loaded and Channels is trying to connect.
     * The connection will also enter this state when it is trying to reconnect after a connection failure.
     */
});

window.Echo.connector.pusher.connection.bind('connected', (payload) => {
    /**
     * The connection to Channels is open and authenticated with your app.
     */
    window.axios.defaults.headers.common['X-Socket-Id'] = payload.socket_id;
    console.log('connected', payload);
});

window.Echo.connector.pusher.connection.bind('unavailable', (payload) => {
    /**
     *  The connection is temporarily unavailable. In most cases this means that there is no internet connection.
     *  It could also mean that Channels is down, or some intermediary is blocking the connection. In this state,
     *  pusher-js will automatically retry the connection every 15 seconds.
     */
});

window.Echo.connector.pusher.connection.bind('failed', (payload) => {
    /**
     * Channels is not supported by the browser.
     * This implies that WebSockets are not natively available and an HTTP-based transport could not be found.
     */
});

window.Echo.connector.pusher.connection.bind('disconnected', (payload) => {
    /**
     * The Channels connection was previously connected and has now intentionally been closed
     */
});

window.Echo.connector.pusher.connection.bind('message', (payload) => {
    /**
     * Ping received from server
     */
});

window.app = {
    name: import.meta.env.VITE_APP_NAME ?? 'company name.',
    url: import.meta.env.VITE_APP_URL ?? 'http://localhost:8000',
}

