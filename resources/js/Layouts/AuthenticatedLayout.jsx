import { useState } from 'react';
import Dropdown from '@/Components/Dropdown';
import { Link } from '@inertiajs/react';

export default function Authenticated({ user, header, children }) {
    const [sidebarOpen, setSidebarOpen] = useState(false);

    const navigation = [
        {
            name: 'محلل المواقع',
            href: route('website.analyzer'),
            icon: '🔍',
            current: route().current('website.analyzer')
        },
        {
            name: 'سجل التحليلات',
            href: route('website.history'),
            icon: '📊',
            current: route().current('website.history')
        },
        {
            name: 'إعدادات الذكاء الاصطناعي',
            href: route('ai-api-settings.index'),
            icon: '🤖',
            current: route().current('ai-api-settings.index')
        },
        {
            name: 'الملف الشخصي',
            href: route('profile.edit'),
            icon: '👤',
            current: route().current('profile.edit')
        }
    ];

    return (
        <div className="min-h-screen bg-gray-50 rtl-container font-arabic">
            {/* Mobile sidebar overlay */}
            {sidebarOpen && (
                <div className="fixed inset-0 z-40 lg:hidden">
                    <div className="fixed inset-0 bg-gray-600 bg-opacity-75" onClick={() => setSidebarOpen(false)}></div>
                </div>
            )}

            {/* Mobile sidebar */}
            <div className={`fixed inset-y-0 right-0 z-50 w-64 bg-white transform transition-transform duration-300 ease-in-out lg:hidden ${
                sidebarOpen ? 'translate-x-0' : 'translate-x-full'
            }`}>
                <div className="flex h-16 items-center justify-between px-4 border-b border-gray-200">
                    <div className="flex items-center">
                        <span className="text-2xl ml-2">🔍</span>
                        <h1 className="text-xl font-bold text-gray-900 heading-primary">AnalyzerDropidea</h1>
                    </div>
                    <button
                        onClick={() => setSidebarOpen(false)}
                        className="text-gray-400 hover:text-gray-600"
                    >
                        <span className="text-xl">✕</span>
                    </button>
                </div>
                <nav className="mt-5 px-2">
                    {navigation.map((item) => (
                        <Link
                            key={item.name}
                            href={item.href}
                            className={`group flex items-center px-2 py-2 text-sm font-medium rounded-md mb-1 transition-colors ${
                                item.current
                                    ? 'gradient-primary text-white'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                            }`}
                        >
                            <span className="text-lg ml-3">{item.icon}</span>
                            <span className="font-cairo">{item.name}</span>
                        </Link>
                    ))}
                </nav>
            </div>

            {/* Desktop sidebar */}
            <div className="hidden lg:fixed lg:inset-y-0 lg:right-0 lg:flex lg:w-64 lg:flex-col">
                <div className="flex min-h-0 flex-1 flex-col bg-white border-l border-gray-200 shadow-lg">
                    <div className="flex flex-1 flex-col overflow-y-auto pt-5 pb-4">
                        {/* Logo */}
                        <div className="flex flex-shrink-0 items-center px-4 mb-8">
                            <div className="flex items-center">
                                <div className="w-10 h-10 gradient-primary rounded-xl flex items-center justify-center ml-3">
                                    <span className="text-xl text-white">🔍</span>
                                </div>
                                <div>
                                    <h1 className="text-xl font-bold text-gray-900 heading-primary">AnalyzerDropidea</h1>
                                    <p className="text-xs text-gray-500 font-almarai">محلل المواقع الاحترافي</p>
                                </div>
                            </div>
                        </div>

                        {/* Navigation */}
                        <nav className="mt-5 flex-1 px-2 space-y-2">
                            {navigation.map((item) => (
                                <Link
                                    key={item.name}
                                    href={item.href}
                                    className={`group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 ${
                                        item.current
                                            ? 'gradient-primary text-white shadow-lg transform scale-105'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 hover:shadow-md'
                                    }`}
                                >
                                    <span className="text-xl ml-4">{item.icon}</span>
                                    <span className="font-cairo">{item.name}</span>
                                </Link>
                            ))}
                        </nav>

                        {/* User info */}
                        <div className="flex-shrink-0 px-4 py-4 border-t border-gray-200">
                            <div className="flex items-center">
                                <div className="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center ml-3">
                                    <span className="text-gray-600 font-bold font-cairo">
                                        {user.name.charAt(0)}
                                    </span>
                                </div>
                                <div className="flex-1">
                                    <p className="text-sm font-medium text-gray-900 font-cairo">{user.name}</p>
                                    <p className="text-xs text-gray-500 font-almarai">{user.email}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Main content */}
            <div className="lg:mr-64">
                {/* Top navigation */}
                <div className="sticky top-0 z-10 bg-white shadow-sm border-b border-gray-200">
                    <div className="mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex h-16 justify-between items-center">
                            {/* Mobile menu button */}
                            <div className="flex items-center lg:hidden">
                                <button
                                    onClick={() => setSidebarOpen(true)}
                                    className="text-gray-500 hover:text-gray-600 focus:outline-none focus:text-gray-600"
                                >
                                    <span className="text-xl">☰</span>
                                </button>
                            </div>

                            {/* Page title */}
                            <div className="flex-1 lg:flex-none">
                                {header && (
                                    <h1 className="text-2xl font-bold text-gray-900 heading-primary">
                                        {header}
                                    </h1>
                                )}
                            </div>

                            {/* Desktop user menu */}
                            <div className="hidden lg:flex lg:items-center lg:space-x-reverse lg:space-x-4">
                                {/* Notifications */}
                                <button className="text-gray-400 hover:text-gray-500 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                                    <span className="text-xl">🔔</span>
                                </button>

                                {/* User dropdown */}
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex rounded-lg">
                                            <button
                                                type="button"
                                                className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-lg text-gray-500 bg-white hover:bg-gray-50 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
                                            >
                                                <div className="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center ml-2">
                                                    <span className="text-gray-600 font-bold text-sm font-cairo">
                                                        {user.name.charAt(0)}
                                                    </span>
                                                </div>
                                                <span className="font-cairo">{user.name}</span>
                                                <span className="text-gray-400 mr-2">▼</span>
                                            </button>
                                        </span>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content>
                                        <Dropdown.Link href={route('profile.edit')} className="font-almarai">
                                            الملف الشخصي
                                        </Dropdown.Link>
                                        <Dropdown.Link href={route('logout')} method="post" as="button" className="font-almarai">
                                            تسجيل الخروج
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>

                            {/* Mobile user menu */}
                            <div className="flex items-center lg:hidden">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex rounded-lg">
                                            <button
                                                type="button"
                                                className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-lg text-gray-500 bg-white hover:bg-gray-50 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
                                            >
                                                <div className="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                                                    <span className="text-gray-600 font-bold text-sm font-cairo">
                                                        {user.name.charAt(0)}
                                                    </span>
                                                </div>
                                                <span className="text-gray-400 mr-2">▼</span>
                                            </button>
                                        </span>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content>
                                        <Dropdown.Link href={route('profile.edit')} className="font-almarai">
                                            الملف الشخصي
                                        </Dropdown.Link>
                                        <Dropdown.Link href={route('logout')} method="post" as="button" className="font-almarai">
                                            تسجيل الخروج
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Page content */}
                <main className="py-6">
                    <div className="mx-auto px-4 sm:px-6 lg:px-8">
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}

