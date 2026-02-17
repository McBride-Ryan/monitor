import { ConnectionStatus } from '../hooks/useConnectionStatus';

const dotColor: Record<ConnectionStatus, string> = {
    connected: '#22c55e',
    connecting: '#eab308',
    disconnected: '#ef4444',
};

const label: Record<ConnectionStatus, string> = {
    connected: 'Live',
    connecting: 'Connecting...',
    disconnected: 'Disconnected — updates paused',
};

export default function ConnectionStatusBanner({ status }: { status: ConnectionStatus }) {
    return (
        <div className="flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium"
            style={{ background: 'transparent', border: '1px solid #334155', color: '#94a3b8' }}>
            <span
                className={status === 'connected' ? 'animate-pulse-dot' : ''}
                style={{
                    display: 'inline-block',
                    width: '8px',
                    height: '8px',
                    borderRadius: '50%',
                    background: dotColor[status],
                }}
            />
            {label[status]}
        </div>
    );
}
