import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import type { Ticket } from '@/types';

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: Echo<any>;
    }
}

let echoInstance: Echo<any> | null = null;

export function getEcho(): Echo<any> {
    if (!echoInstance) {
        window.Pusher = Pusher;

        echoInstance = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS:
                (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
            withCredentials: true,
        });
    }

    return echoInstance;
}

/** Listen on private finance channel (authenticated) */
export function useFinanceChannel(
    onCreated: (ticket: Ticket) => void,
    onUpdated: (ticket: Ticket) => void,
) {
    const echo = getEcho();
    const channel = echo.private('finance');

    channel.listen('.ticket.created', (e: { ticket: Ticket }) =>
        onCreated(e.ticket),
    );
    channel.listen('.ticket.status.updated', (e: { ticket: Ticket }) =>
        onUpdated(e.ticket),
    );

    return () => echo.leave('finance');
}

/** Listen on public monitor channel */
export function useMonitorChannel(
    onCreated: (ticket: Ticket) => void,
    onUpdated: (ticket: Ticket) => void,
) {
    const echo = getEcho();
    const channel = echo.channel('monitor');

    channel.listen('.ticket.created', (e: { ticket: Ticket }) =>
        onCreated(e.ticket),
    );
    channel.listen('.ticket.status.updated', (e: { ticket: Ticket }) =>
        onUpdated(e.ticket),
    );

    return () => echo.leave('monitor');
}

/** Listen on public station channel */
export function useStationChannel(
    stationId: string,
    onUpdated: (ticket: Ticket) => void,
) {
    const echo = getEcho();
    const channelName = `station.${stationId}`;
    const channel = echo.channel(channelName);

    channel.listen('.ticket.created', (e: { ticket: Ticket }) =>
        onUpdated(e.ticket),
    );
    channel.listen('.ticket.status.updated', (e: { ticket: Ticket }) =>
        onUpdated(e.ticket),
    );

    return () => echo.leave(channelName);
}
