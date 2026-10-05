import { usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';

let echoPromise = null;

/** Lazily create the Echo client (pusher-js only downloads for signed-in team members). */
export function getEcho() {
    if (!import.meta.env.VITE_REVERB_APP_KEY) {
        return Promise.resolve(null);
    }

    echoPromise ??= Promise.all([import('laravel-echo'), import('pusher-js')]).then(([{ default: Echo }, { default: Pusher }]) => {
        window.Pusher = Pusher;

        return new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        });
    });

    return echoPromise;
}

/**
 * Subscribe to a private channel for the component's lifetime.
 * `listeners` maps event names (".activity.recorded") to handlers.
 * Silently does nothing when realtime is disabled or the socket is unreachable.
 */
export function useRealtime(channelName, listeners) {
    const page = usePage();
    let channel = null;
    let disposed = false;

    onMounted(async () => {
        const name = typeof channelName === 'function' ? channelName() : channelName;

        if (!page.props.app?.realtime || !name) return;

        const echo = await getEcho().catch(() => null);
        if (!echo || disposed) return;

        channel = echo.private(name);

        for (const [event, handler] of Object.entries(listeners)) {
            event === 'notification' ? channel.notification(handler) : channel.listen(event, handler);
        }
    });

    onBeforeUnmount(() => {
        disposed = true;

        if (channel) {
            // Remove only this component's handlers; other subscribers share the channel.
            for (const [event, handler] of Object.entries(listeners)) {
                event === 'notification' ? channel.stopListeningForNotification(handler) : channel.stopListening(event, handler);
            }
        }
    });
}
