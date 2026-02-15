import { useState, useEffect } from 'react';
import echo from '../echo';

export type ConnectionStatus = 'connected' | 'connecting' | 'disconnected';

export function useConnectionStatus() {
    const [status, setStatus] = useState<ConnectionStatus>('connecting');

    useEffect(() => {
        const connector = echo.connector as any;
        const pusher = connector?.pusher;

        if (!pusher) {
            setStatus('disconnected');
            return;
        }

        pusher.connection.bind('connected', () => setStatus('connected'));
        pusher.connection.bind('connecting', () => setStatus('connecting'));
        pusher.connection.bind('unavailable', () => setStatus('disconnected'));
        pusher.connection.bind('failed', () => setStatus('disconnected'));
        pusher.connection.bind('disconnected', () => setStatus('disconnected'));

        if (pusher.connection.state === 'connected') {
            setStatus('connected');
        }

        return () => {
            pusher.connection.unbind('connected');
            pusher.connection.unbind('connecting');
            pusher.connection.unbind('unavailable');
            pusher.connection.unbind('failed');
            pusher.connection.unbind('disconnected');
        };
    }, []);

    return status;
}
