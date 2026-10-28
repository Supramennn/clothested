import Pusher from 'pusher-js';
import Echo from 'laravel-echo';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

/*
 * Section 5.9: one private subscription per live chat session. Each channel
 * event relays into the Livewire components as a named browser event.
 */
const subscriptions = new Map();

const subscribeToChat = (sessionId) => {
    if (!sessionId || !window.Echo || subscriptions.has(sessionId)) {
        return;
    }

    const channel = window.Echo.private(`chat.${sessionId}`);

    channel.listen('.message.received', (payload) => {
        window.Livewire?.dispatch('chat.message.received', { chatSessionId: payload.chat_session_id });
    });

    channel.listen('.message.read', (payload) => {
        window.Livewire?.dispatch('chat.read.updated', { chatSessionId: payload.chat_session_id });
    });

    channel.listen('.session.closed', (payload) => {
        window.Livewire?.dispatch('chat.session.closed', { chatSessionId: payload.chat_session_id });
    });

    subscriptions.set(sessionId, channel);
};

window.addEventListener('chat:subscribe', (event) => subscribeToChat(event.detail?.chatSessionId));
window.addEventListener('chat.subscribe', (event) => subscribeToChat(event.detail?.chatSessionId));
