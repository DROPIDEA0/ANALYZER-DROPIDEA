import React, { useState, useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import axios from 'axios';

export default function BusinessAnalyzer({ auth, analysis, googleMapsApiKey }) {
    const [isAnalyzing, setIsAnalyzing] = useState(false);
    const [analysisMode, setAnalysisMode] = useState('website'); // 'website' or 'business'
    const [businessSuggestions, setBusinessSuggestions] = useState([]);
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [isSearching, setIsSearching] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        url: '',
        region: 'global',
        analysis_type: 'full',
        business_name: '',
        business_category: '',
        country: 'السعودية'
    });

    // فئات الأعمال التجارية
    const businessCategories = [
        { value: 'restaurant', label: 'مطاعم', icon: '🍽️' },
        { value: 'beauty_salon', label: 'مراكز التجميل', icon: '💄' },
        { value: 'lawyer', label: 'مكاتب المحاماة', icon: '⚖️' },
        { value: 'hospital', label: 'مستشفيات وعيادات', icon: '🏥' },
        { value: 'school', label: 'مدارس ومعاهد', icon: '🎓' },
        { value: 'gym', label: 'نوادي رياضية', icon: '💪' },
        { value: 'shopping_mall', label: 'مراكز تسوق', icon: '🛍️' },
        { value: 'car_repair', label: 'ورش السيارات', icon: '🔧' },
        { value: 'real_estate_agency', label: 'مكاتب عقارية', icon: '🏠' },
        { value: 'accounting', label: 'مكاتب محاسبة', icon: '📊' },
        { value: 'pharmacy', label: 'صيدليات', icon: '💊' },
        { value: 'gas_station', label: 'محطات وقود', icon: '⛽' }
    ];

    // البحث عن الأعمال التجارية
    const searchBusiness = async (query, category = '') => {
        if (query.length < 3) {
            setBusinessSuggestions([]);
            setShowSuggestions(false);
            return;
        }

        setIsSearching(true);
        try {
            const response = await axios.get(route('website.search.business'), {
                params: {
                    query: query,
                    category: category,
                    country: data.country || 'السعودية'
                }
            });

            if (response.data.success) {
                setBusinessSuggestions(response.data.businesses);
                setShowSuggestions(true);
            }
        } catch (error) {
            console.error('فشل في البحث:', error);
        } finally {
            setIsSearching(false);
        }
    };

    const selectBusiness = (business) => {
        setData('business_name', business.name);
        setBusinessSuggestions([]);
        setShowSuggestions(false);
    };

    const submit = (e) => {
        e.preventDefault();
        setIsAnalyzing(true);

        // تحديد الـ endpoint بناءً على نوع التحليل
        const endpoint = analysisMode === 'business'
            ? route('website.analyze.business')
            : route('website.analyze');

        post(endpoint, {
            onFinish: () => {
                setIsAnalyzing(false);
            },
            onError: (errors) => {
                setIsAnalyzing(false);
                console.error('Analysis errors:', errors);
            }
        });
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="محلل المواقع والأعمال التجارية">
                {googleMapsApiKey && (
                    <script
                        src={`https://maps.googleapis.com/maps/api/js?key=${googleMapsApiKey}&libraries=places&language=ar`}
                        async
                        defer
                    />
                )}
            </Head>

            <div className="min-h-screen bg-gray-50">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div className="text-center mb-8">
                        <h1 className="text-4xl font-bold text-gray-900 mb-4">
                            محلل المواقع والأعمال التجارية
                        </h1>
                        <p className="text-xl text-gray-600 max-w-3xl mx-auto">
                            احصل على تحليل شامل ومتقدم للمواقع الإلكترونية أو ابحث عن الأعمال التجارية وقم بتحليلها
                        </p>
                    </div>

                    {/* تابات الاختيار */}
                    <div className="flex justify-center mb-8">
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-1 inline-flex">
                            <button
                                onClick={() => setAnalysisMode('website')}
                                className={`px-6 py-3 rounded-lg font-medium transition-all duration-200 ${
                                    analysisMode === 'website'
                                        ? 'bg-blue-500 text-white shadow-sm'
                                        : 'text-gray-600 hover:text-blue-600'
                                }`}
                            >
                                <span className="flex items-center">
                                    <span className="ml-2">🌐</span>
                                    تحليل رابط الموقع
                                </span>
                            </button>
                            <button
                                onClick={() => setAnalysisMode('business')}
                                className={`px-6 py-3 rounded-lg font-medium transition-all duration-200 ${
                                    analysisMode === 'business'
                                        ? 'bg-green-500 text-white shadow-sm'
                                        : 'text-gray-600 hover:text-green-600'
                                }`}
                            >
                                <span className="flex items-center">
                                    <span className="ml-2">🏢</span>
                                    البحث عن عمل تجاري
                                </span>
                            </button>
                        </div>
                    </div>

                    {!analysis && (
                        <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-8">
                            {analysisMode === 'website' ? (
                                // نموذج تحليل الموقع
                                <form onSubmit={submit} className="space-y-6">
                                    <div className="text-center mb-6">
                                        <h3 className="text-2xl font-bold text-blue-900 mb-2">
                                            🌐 تحليل موقع إلكتروني
                                        </h3>
                                        <p className="text-gray-600">أدخل رابط الموقع للحصول على تحليل شامل</p>
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="url" value="رابط الموقع" />
                                        <TextInput
                                            id="url"
                                            name="url"
                                            value={data.url}
                                            className="mt-1 block w-full text-lg"
                                            autoComplete="url"
                                            placeholder="https://example.com"
                                            onChange={(e) => setData('url', e.target.value)}
                                            required
                                        />
                                        <InputError message={errors.url} className="mt-2" />
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <InputLabel htmlFor="region" value="المنطقة الجغرافية" />
                                            <select
                                                id="region"
                                                name="region"
                                                value={data.region}
                                                className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                onChange={(e) => setData('region', e.target.value)}
                                            >
                                                <option value="global">عالمي</option>
                                                <option value="saudi">السعودية</option>
                                                <option value="uae">الإمارات</option>
                                                <option value="egypt">مصر</option>
                                                <option value="jordan">الأردن</option>
                                            </select>
                                        </div>

                                        <div>
                                            <InputLabel htmlFor="analysis_type" value="نوع التحليل" />
                                            <select
                                                id="analysis_type"
                                                name="analysis_type"
                                                value={data.analysis_type}
                                                className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                onChange={(e) => setData('analysis_type', e.target.value)}
                                            >
                                                <option value="full">تحليل شامل (موصى به)</option>
                                                <option value="seo">تحليل تحسين محركات البحث</option>
                                                <option value="performance">تحليل الأداء</option>
                                                <option value="competitors">تحليل المنافسين</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div className="flex justify-center">
                                        <PrimaryButton
                                            className="px-8 py-4 text-lg font-semibold bg-blue-500 hover:bg-blue-600"
                                            disabled={processing || isAnalyzing}
                                        >
                                            {processing || isAnalyzing ? (
                                                <div className="flex items-center">
                                                    <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white ml-3"></div>
                                                    جاري التحليل...
                                                </div>
                                            ) : (
                                                <div className="flex items-center">
                                                    <svg className="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                                    </svg>
                                                    تحليل الموقع
                                                </div>
                                            )}
                                        </PrimaryButton>
                                    </div>
                                </form>
                            ) : (
                                // نموذج البحث عن الأعمال التجارية
                                <form onSubmit={submit} className="space-y-6">
                                    <div className="text-center mb-6">
                                        <h3 className="text-2xl font-bold text-green-900 mb-2">
                                            🏢 البحث عن عمل تجاري
                                        </h3>
                                        <p className="text-gray-600">ابحث عن الأعمال التجارية وقم بتحليلها</p>
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <InputLabel htmlFor="business_category" value="فئة العمل التجاري" />
                                            <select
                                                id="business_category"
                                                name="business_category"
                                                value={data.business_category}
                                                className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500"
                                                onChange={(e) => setData('business_category', e.target.value)}
                                                required
                                            >
                                                <option value="">اختر فئة العمل</option>
                                                {businessCategories.map(category => (
                                                    <option key={category.value} value={category.value}>
                                                        {category.icon} {category.label}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError message={errors.business_category} className="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel htmlFor="country" value="الدولة" />
                                            <select
                                                id="country"
                                                name="country"
                                                value={data.country}
                                                className="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500"
                                                onChange={(e) => setData('country', e.target.value)}
                                            >
                                                <option value="السعودية">🇸🇦 المملكة العربية السعودية</option>
                                                <option value="الامارات">🇦🇪 دولة الإمارات العربية المتحدة</option>
                                                <option value="الكويت">🇰🇼 دولة الكويت</option>
                                                <option value="قطر">🇶🇦 دولة قطر</option>
                                                <option value="البحرين">🇧🇭 مملكة البحرين</option>
                                                <option value="عمان">🇴🇲 سلطنة عمان</option>
                                                <option value="الأردن">🇯🇴 المملكة الأردنية الهاشمية</option>
                                                <option value="مصر">🇪🇬 جمهورية مصر العربية</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div className="relative">
                                        <InputLabel htmlFor="business_name" value="اسم العمل التجاري" />
                                        <TextInput
                                            id="business_name"
                                            name="business_name"
                                            value={data.business_name}
                                            className="mt-1 block w-full text-lg"
                                            placeholder="ابحث عن اسم العمل التجاري..."
                                            onChange={(e) => {
                                                setData('business_name', e.target.value);
                                                searchBusiness(e.target.value, data.business_category);
                                            }}
                                            required
                                        />
                                        <InputError message={errors.business_name} className="mt-2" />

                                        {/* اقتراحات البحث */}
                                        {showSuggestions && businessSuggestions.length > 0 && (
                                            <div className="absolute top-full left-0 right-0 bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto z-50">
                                                {businessSuggestions.map((business, index) => (
                                                    <button
                                                        key={index}
                                                        type="button"
                                                        className="w-full text-right p-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0 transition-colors"
                                                        onClick={() => selectBusiness(business)}
                                                    >
                                                        <div className="font-medium text-gray-900">{business.name}</div>
                                                        <div className="text-sm text-gray-500 mt-1">{business.address}</div>
                                                        {business.rating > 0 && (
                                                            <div className="flex items-center mt-1">
                                                                <span className="text-yellow-400 ml-1">⭐</span>
                                                                <span className="text-sm font-medium">{business.rating}</span>
                                                            </div>
                                                        )}
                                                    </button>
                                                ))}
                                            </div>
                                        )}

                                        {/* مؤشر البحث */}
                                        {isSearching && (
                                            <div className="absolute left-3 top-10 transform -translate-y-1/2">
                                                <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-green-500"></div>
                                            </div>
                                        )}
                                    </div>

                                    <div className="flex justify-center">
                                        <PrimaryButton
                                            className="px-8 py-4 text-lg font-semibold bg-green-500 hover:bg-green-600"
                                            disabled={processing || isAnalyzing}
                                        >
                                            {processing || isAnalyzing ? (
                                                <div className="flex items-center">
                                                    <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white ml-3"></div>
                                                    جاري التحليل...
                                                </div>
                                            ) : (
                                                <div className="flex items-center">
                                                    <svg className="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                    </svg>
                                                    تحليل العمل التجاري
                                                </div>
                                            )}
                                        </PrimaryButton>
                                    </div>
                                </form>
                            )}
                        </div>
                    )}

                    {/* عرض النتائج */}
                    {analysis && (
                        <div className="space-y-6">
                            <div className="bg-white rounded-xl shadow-sm border p-6">
                                <h2 className="text-2xl font-bold text-gray-900 mb-4">
                                    {analysis.type === 'business_analysis' ? 'تحليل العمل التجاري' : 'تحليل الموقع'}
                                </h2>
                                
                                {analysis.type === 'business_analysis' && (
                                    <div className="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                                        <h3 className="font-semibold text-green-900 mb-2">{analysis.business_name}</h3>
                                        <p className="text-green-700">
                                            فئة: {businessCategories.find(cat => cat.value === analysis.business_category)?.label || analysis.business_category}
                                        </p>
                                        <p className="text-green-700">الدولة: {analysis.country}</p>
                                        <div className="mt-3">
                                            <span className="text-2xl font-bold text-green-800">{analysis.overall_score}%</span>
                                            <span className="text-green-600 mr-2">النتيجة الإجمالية</span>
                                        </div>
                                    </div>
                                )}

                                {analysis.gmb_data && (
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                            <h4 className="font-semibold text-blue-900 mb-1">التقييم</h4>
                                            <div className="flex items-center">
                                                <span className="text-yellow-400 ml-1">⭐</span>
                                                <span className="font-bold">{analysis.gmb_data.rating || 'غير محدد'}</span>
                                                {analysis.gmb_data.reviews_count > 0 && (
                                                    <span className="text-gray-500 mr-1">({analysis.gmb_data.reviews_count} مراجعة)</span>
                                                )}
                                            </div>
                                        </div>

                                        <div className="bg-purple-50 border border-purple-200 rounded-lg p-4">
                                            <h4 className="font-semibold text-purple-900 mb-1">الموقع الإلكتروني</h4>
                                            <span className={`text-sm ${analysis.gmb_data.website ? 'text-green-600' : 'text-red-600'}`}>
                                                {analysis.gmb_data.website ? '✓ متوفر' : '✗ غير متوفر'}
                                            </span>
                                        </div>

                                        <div className="bg-orange-50 border border-orange-200 rounded-lg p-4">
                                            <h4 className="font-semibold text-orange-900 mb-1">رقم الهاتف</h4>
                                            <span className={`text-sm ${analysis.gmb_data.phone ? 'text-green-600' : 'text-red-600'}`}>
                                                {analysis.gmb_data.phone ? '✓ متوفر' : '✗ غير متوفر'}
                                            </span>
                                        </div>
                                    </div>
                                )}

                                {analysis.recommendations && analysis.recommendations.length > 0 && (
                                    <div className="mt-6">
                                        <h4 className="font-semibold text-gray-900 mb-3">التوصيات للتحسين:</h4>
                                        <ul className="space-y-2">
                                            {analysis.recommendations.map((recommendation, index) => (
                                                <li key={index} className="flex items-start">
                                                    <span className="text-blue-500 ml-2">•</span>
                                                    <span className="text-gray-700">{recommendation}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}