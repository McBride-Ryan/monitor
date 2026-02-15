import { ConnectionStatus } from '../hooks/useConnectionStatus';

const statusConfig: Record<ConnectionStatus, { bg: string; text: string; label: string }> = {
    connected: { bg: 'bg-green-100', text: 'text-green-800', label: 'Live' },
    connecting: { bg: 'bg-yellow-100', text: 'text-yellow-800', label: 'Connecting...' },
    disconnected: { bg: 'bg-red-100', text: 'text-red-800', label: 'Disconnected — updates paused' },
};

export default function ConnectionStatusBanner({ status }: { status: ConnectionStatus }) {
    const config = statusConfig[status];

    return (
        <div className={`${config.bg} ${config.text} px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2`}>
            <span className={`inline-block w-2 h-2 rounded-full ${
                status === 'connected' ? 'bg-green-500' :
                status === 'connecting' ? 'bg-yellow-500' : 'bg-red-500'
            }`} />
            {config.label}
        </div>
    );
}
