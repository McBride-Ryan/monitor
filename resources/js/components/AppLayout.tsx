import { ReactNode } from 'react';
import { Link } from '@inertiajs/react';

interface Props {
    children: ReactNode;
    title: string;
}

export default function AppLayout({ children, title }: Props) {
    const currentPath = window.location.pathname;

    const navItems = [
        { href: '/', label: 'Transactions' },
        { href: '/vendor-import', label: 'Vendor Import' },
        { href: '/audits', label: 'Data Audits' },
    ];

    return (
        <div className="min-h-screen bg-gray-50">
            <nav className="bg-white border-b border-gray-200">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between h-16">
                        <div className="flex items-center space-x-8">
                            <h1 className="text-lg font-bold text-gray-900">ERP Monitor</h1>
                            <div className="flex space-x-4">
                                {navItems.map(item => (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        className={`px-3 py-2 rounded-md text-sm font-medium transition-colors ${
                                            currentPath === item.href
                                                ? 'bg-gray-100 text-gray-900'
                                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                        }`}
                                    >
                                        {item.label}
                                    </Link>
                                ))}
                            </div>
                        </div>
                        <div className="text-sm text-gray-500">{title}</div>
                    </div>
                </div>
            </nav>
            {children}
        </div>
    );
}
