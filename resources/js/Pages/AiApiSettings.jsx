import { useState, useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

export default function AiApiSettings({ auth, providers, userSettings, status }) {
    const [activeTab, setActiveTab] = useState('openai');
    const [testingConnection, setTestingConnection] = useState({});
    const [showApiKey, setShowApiKey] = useState({});

    // تحويل userSettings إلى object للوصول السهل
    const settingsMap = userSettings.reduce((acc, setting) => {
        acc[setting.provider] = setting;
        return acc;
    }, {});

    const { data, setData, post, processing, errors, reset } = useForm({
        provider: 'openai',
        api_key: '',
        api_base_url: '',
        model: '',
        is_active: false,
        settings: {},
    });

    // تحديث البيانات عند تغيير التبويب أو تحديث userSettings
    useEffect(() => {
        const currentSetting = settingsMap[activeTab];
        if (currentSetting && currentSetting.id) {
            // إعداد موجود - استخدم البيانات المحفوظة
            setData({
                provider: currentSetting.provider,
                api_key: data.api_key || '', // احتفظ بالمفتاح المدخل إن وجد
                api_base_url: currentSetting.api_base_url || '',
                model: currentSetting.model || '',
                is_active: currentSetting.is_active || false,
                settings: currentSetting.settings || {},
            });
        } else {
            // إعداد جديد - استخدم القيم الافتراضية
            const defaultSettings = providers[activeTab];
            setData({
                provider: activeTab,
                api_key: data.api_key || '', // احتفظ بالمفتاح المدخل
                api_base_url: defaultSettings?.api_base_url || '',
                model: defaultSettings?.models?.[0] || '',
                is_active: false,
                settings: {},
            });
        }
    }, [activeTab, userSettings]);

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('ai-api-settings.store'), {
            onSuccess: () => {
                // لا نعيد تعيين النموذج للحفاظ على البيانات المدخلة
                // سيتم تحديث البيانات تلقائياً من خلال userSettings
            },
            preserveScroll: true,
        });
    };

    const testConnection = async (provider) => {
        setTestingConnection(prev => ({ ...prev, [provider]: true }));
        
        try {
            const response = await fetch(route('ai-api-settings.test'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    provider: data.provider,
                    api_key: data.api_key,
                    api_base_url: data.api_base_url,
                    model: data.model,
                }),
            });

            const result = await response.json();
            
            if (result.success) {
                alert('✅ ' + result.message);
            } else {
                alert('❌ ' + result.message);
            }
        } catch (error) {
            alert('❌ خطأ في الاتصال: ' + error.message);
        } finally {
            setTestingConnection(prev => ({ ...prev, [provider]: false }));
        }
    };

    const toggleApiKeyVisibility = (provider) => {
        setShowApiKey(prev => ({
            ...prev,
            [provider]: !prev[provider]
        }));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header="إعدادات الذكاء الاصطناعي"
        >
            <Head title="إعدادات الذكاء الاصطناعي" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* رسالة الحالة */}
                    {status && (
                        <div className={`mb-6 p-4 rounded-xl ${
                            status.type === 'success' 
                                ? 'bg-green-50 border border-green-200 text-green-800' 
                                : 'bg-red-50 border border-red-200 text-red-800'
                        }`}>
                            <div className="flex items-center">
                                <span className="text-lg ml-2">
                                    {status.type === 'success' ? '✅' : '❌'}
                                </span>
                                <span className="font-almarai">{status.message}</span>
                            </div>
                        </div>
                    )}

                    {/* العنوان والوصف */}
                    <div className="mb-8">
                        <h1 className="text-3xl font-bold text-gray-900 heading-primary mb-2">
                            إعدادات الذكاء الاصطناعي
                        </h1>
                        <p className="text-gray-600 font-almarai">
                            قم بربط حسابات الذكاء الاصطناعي لتحسين دقة وشمولية تقارير تحليل المواقع
                        </p>
                    </div>

                    {/* إحصائيات سريعة */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        {Object.entries(providers).map(([key, provider]) => {
                            const setting = settingsMap[key];
                            const isActive = setting?.is_active || false;
                            
                            return (
                                <div key={key} className="bg-white rounded-xl shadow-arabic p-6 border border-gray-100">
                                    <div className="flex items-center justify-between mb-4">
                                        <div className="flex items-center">
                                            <span className="text-2xl ml-3">{provider.icon}</span>
                                            <div>
                                                <h3 className="font-bold text-gray-900 font-cairo">{provider.name}</h3>
                                                <p className="text-sm text-gray-500 font-almarai">{provider.description}</p>
                                            </div>
                                        </div>
                                        <div className={`w-3 h-3 rounded-full ${
                                            isActive ? 'bg-green-500' : 'bg-gray-300'
                                        }`}></div>
                                    </div>
                                    <div className="text-sm text-gray-600 font-almarai">
                                        الحالة: <span className={`font-bold ${
                                            isActive ? 'text-green-600' : 'text-gray-500'
                                        }`}>
                                            {isActive ? 'مفعل' : 'غير مفعل'}
                                        </span>
                                        {setting?.has_api_key && (
                                            <div className="text-xs text-blue-600 mt-1">
                                                ✓ تم حفظ مفتاح API
                                            </div>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {/* التبويبات */}
                    <div className="bg-white rounded-xl shadow-arabic overflow-hidden">
                        {/* رؤوس التبويبات */}
                        <div className="border-b border-gray-200">
                            <nav className="flex">
                                {Object.entries(providers).map(([key, provider]) => (
                                    <button
                                        key={key}
                                        onClick={() => setActiveTab(key)}
                                        className={`flex items-center px-6 py-4 text-sm font-medium transition-colors ${
                                            activeTab === key
                                                ? 'border-b-2 border-blue-500 text-blue-600 bg-blue-50'
                                                : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'
                                        }`}
                                    >
                                        <span className="text-lg ml-2">{provider.icon}</span>
                                        <span className="font-cairo">{provider.name}</span>
                                        {settingsMap[key]?.is_active && (
                                            <span className="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                        )}
                                    </button>
                                ))}
                            </nav>
                        </div>

                        {/* محتوى التبويب */}
                        <div className="p-6">
                            <form onSubmit={handleSubmit} className="space-y-6">
                                {/* معلومات المزود */}
                                <div className="bg-gray-50 rounded-lg p-4">
                                    <div className="flex items-center mb-3">
                                        <span className="text-2xl ml-3">{providers[activeTab]?.icon}</span>
                                        <div>
                                            <h3 className="font-bold text-gray-900 font-cairo">
                                                {providers[activeTab]?.name}
                                            </h3>
                                            <p className="text-sm text-gray-600 font-almarai">
                                                {providers[activeTab]?.description}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {/* حقل API Key */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2 font-cairo">
                                        مفتاح API *
                                    </label>
                                    <div className="relative">
                                        <input
                                            type={showApiKey[activeTab] ? 'text' : 'password'}
                                            value={data.api_key}
                                            onChange={(e) => setData('api_key', e.target.value)}
                                            className="input-arabic w-full pl-10"
                                            placeholder={settingsMap[activeTab]?.has_api_key ? 'تم حفظ المفتاح - أدخل مفتاح جديد للتغيير' : 'أدخل مفتاح API'}
                                            required={!settingsMap[activeTab]?.has_api_key}
                                        />
                                        <button
                                            type="button"
                                            onClick={() => toggleApiKeyVisibility(activeTab)}
                                            className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                        >
                                            {showApiKey[activeTab] ? '🙈' : '👁️'}
                                        </button>
                                    </div>
                                    {errors.api_key && (
                                        <p className="mt-1 text-sm text-red-600 font-almarai">{errors.api_key}</p>
                                    )}
                                </div>

                                {/* حقل Base URL */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2 font-cairo">
                                        رابط API الأساسي
                                    </label>
                                    <input
                                        type="url"
                                        value={data.api_base_url}
                                        onChange={(e) => setData('api_base_url', e.target.value)}
                                        className="input-arabic w-full"
                                        placeholder="https://api.example.com"
                                    />
                                    {errors.api_base_url && (
                                        <p className="mt-1 text-sm text-red-600 font-almarai">{errors.api_base_url}</p>
                                    )}
                                </div>

                                {/* حقل النموذج */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2 font-cairo">
                                        النموذج
                                    </label>
                                    <select
                                        value={data.model}
                                        onChange={(e) => setData('model', e.target.value)}
                                        className="input-arabic w-full"
                                    >
                                        <option value="">اختر النموذج</option>
                                        {providers[activeTab]?.models?.map((model) => (
                                            <option key={model} value={model}>{model}</option>
                                        ))}
                                    </select>
                                    {errors.model && (
                                        <p className="mt-1 text-sm text-red-600 font-almarai">{errors.model}</p>
                                    )}
                                </div>

                                {/* مفتاح التفعيل */}
                                <div className="flex items-center">
                                    <input
                                        type="checkbox"
                                        id="is_active"
                                        checked={data.is_active}
                                        onChange={(e) => setData('is_active', e.target.checked)}
                                        className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded ml-3"
                                    />
                                    <label htmlFor="is_active" className="text-sm font-medium text-gray-700 font-cairo">
                                        تفعيل هذا المزود
                                    </label>
                                </div>

                                {/* أزرار الإجراءات */}
                                <div className="flex items-center justify-between pt-6 border-t border-gray-200">
                                    <div className="flex space-x-reverse space-x-3">
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="btn-arabic gradient-primary text-white px-6 py-2 rounded-lg font-cairo disabled:opacity-50"
                                        >
                                            {processing ? 'جاري الحفظ...' : 'حفظ الإعدادات'}
                                        </button>
                                        
                                        <button
                                            type="button"
                                            onClick={() => testConnection(activeTab)}
                                            disabled={testingConnection[activeTab] || !data.api_key}
                                            className="btn-arabic bg-gray-100 text-gray-700 hover:bg-gray-200 px-6 py-2 rounded-lg font-cairo disabled:opacity-50"
                                        >
                                            {testingConnection[activeTab] ? 'جاري الاختبار...' : 'اختبار الاتصال'}
                                        </button>
                                    </div>

                                    {settingsMap[activeTab]?.id && (
                                        <button
                                            type="button"
                                            onClick={() => {
                                                if (confirm('هل أنت متأكد من حذف هذه الإعدادات؟')) {
                                                    const form = document.createElement('form');
                                                    form.method = 'POST';
                                                    form.action = route('ai-api-settings.destroy', settingsMap[activeTab].id);
                                                    
                                                    const csrfToken = document.createElement('input');
                                                    csrfToken.type = 'hidden';
                                                    csrfToken.name = '_token';
                                                    csrfToken.value = document.querySelector('meta[name="csrf-token"]').content;
                                                    
                                                    const methodField = document.createElement('input');
                                                    methodField.type = 'hidden';
                                                    methodField.name = '_method';
                                                    methodField.value = 'DELETE';
                                                    
                                                    form.appendChild(csrfToken);
                                                    form.appendChild(methodField);
                                                    document.body.appendChild(form);
                                                    form.submit();
                                                }
                                            }}
                                            className="text-red-600 hover:text-red-800 font-cairo text-sm"
                                        >
                                            حذف الإعدادات
                                        </button>
                                    )}
                                </div>
                            </form>
                        </div>
                    </div>

                    {/* معلومات إضافية وحالة النظام */}
                    <div className="mt-8 grid md:grid-cols-2 gap-6">
                        {/* نصائح مهمة */}
                        <div className="bg-blue-50 border border-blue-200 rounded-xl p-6">
                            <h3 className="font-bold text-blue-900 mb-3 font-cairo">💡 نصائح مهمة</h3>
                            <ul className="space-y-2 text-blue-800 font-almarai text-sm">
                                <li>• احرص على حماية مفاتيح API وعدم مشاركتها مع أحد</li>
                                <li>• يمكنك تفعيل عدة مزودين في نفس الوقت للحصول على تحليل أكثر شمولية</li>
                                <li>• استخدم زر "اختبار الاتصال" للتأكد من صحة الإعدادات قبل الحفظ</li>
                                <li>• سيتم استخدام المزودين المفعلين في تحليل المواقع تلقائياً</li>
                            </ul>
                        </div>

                        {/* حالة النظام */}
                        <div className="bg-green-50 border border-green-200 rounded-xl p-6">
                            <h3 className="font-bold text-green-900 mb-3 font-cairo">📊 حالة النظام</h3>
                            <div className="space-y-2 text-sm font-almarai">
                                {Object.entries(providers).map(([key, provider]) => {
                                    const setting = settingsMap[key];
                                    const isConfigured = setting?.has_api_key && setting?.is_active;
                                    
                                    return (
                                        <div key={key} className="flex items-center justify-between">
                                            <span className="text-gray-700">{provider.name}</span>
                                            <span className={`text-xs px-2 py-1 rounded-full ${
                                                isConfigured 
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-gray-100 text-gray-600'
                                            }`}>
                                                {isConfigured ? 'جاهز' : 'غير مكوّن'}
                                            </span>
                                        </div>
                                    );
                                })}
                                <div className="mt-3 pt-3 border-t border-green-200">
                                    <div className="flex items-center justify-between font-bold">
                                        <span className="text-green-900">المزودين النشطين:</span>
                                        <span className="text-green-700">
                                            {Object.values(settingsMap).filter(s => s?.has_api_key && s?.is_active).length} / {Object.keys(providers).length}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

