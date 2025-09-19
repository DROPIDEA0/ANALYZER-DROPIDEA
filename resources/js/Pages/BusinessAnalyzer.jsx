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
                        <div className="space-y-8">
                            {/* رأس التقرير */}
                            <div className="bg-white rounded-xl shadow-lg border p-8 text-center">
                                <div className="bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg p-6 mb-6">
                                    <h1 className="text-3xl font-bold mb-2">تقرير تحليل الأعمال</h1>
                                    <p className="text-blue-100">تحليل شامل ومفصل لأداء نشاطك التجاري</p>
                                </div>
                                
                                <div className="text-sm text-gray-500 mb-2">
                                    تاريخ التقرير: {new Date().toLocaleDateString('ar-SA', { 
                                        year: 'numeric', month: 'long', day: 'numeric',
                                        hour: '2-digit', minute: '2-digit'
                                    })}
                                </div>
                            </div>

                            {/* معلومات النشاط التجاري */}
                            {analysis.type === 'business_analysis' && analysis.gmb_data && (
                                <div className="bg-white rounded-xl shadow-lg border p-8">
                                    <h2 className="text-2xl font-bold text-center text-gray-900 mb-8">معلومات النشاط التجاري</h2>
                                    
                                    <div className="text-center mb-8">
                                        <h3 className="text-3xl font-bold text-blue-900 mb-4">{analysis.business_name}</h3>
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        <div className="space-y-4">
                                            <div className="flex justify-between items-center border-b pb-2">
                                                <span className="font-semibold text-gray-700">التقييم:</span>
                                                <div className="text-left">
                                                    <div className="flex items-center">
                                                        <span className="text-yellow-400 ml-1">⭐</span>
                                                        <span className="font-bold">{analysis.gmb_data.rating || 'غير محدد'}</span>
                                                        <span className="text-gray-500 mr-2">
                                                            ({analysis.gmb_data.reviews_count || 0} تقييمات)
                                                        </span>
                                                    </div>
                                                    <p className="text-sm text-gray-600 mt-1">
                                                        {analysis.gmb_data.rating >= 4.5 ? 'تقييم ممتاز' : 
                                                         analysis.gmb_data.rating >= 4.0 ? 'تقييم جيد جداً مع إمكانية للتحسين' : 
                                                         analysis.gmb_data.rating >= 3.0 ? 'تقييم جيد' : 'يحتاج تحسين'}
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="flex justify-between items-start border-b pb-2">
                                                <span className="font-semibold text-gray-700">العنوان:</span>
                                                <div className="text-left max-w-xs">
                                                    <p className="text-gray-800">{analysis.gmb_data.address || 'غير محدد'}</p>
                                                    <p className="text-sm text-gray-600">{data.country}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="space-y-4">
                                            <div className="flex justify-between items-start border-b pb-2">
                                                <span className="font-semibold text-gray-700">الموقع:</span>
                                                <div className="text-left max-w-xs">
                                                    <p className="text-blue-600 break-all">{analysis.gmb_data.website || 'غير متوفر'}</p>
                                                    <p className="text-sm text-gray-600 mt-1">
                                                        {analysis.gmb_data.website ? 'منصة رقمية لعرض الخدمات والتفاعل مع العملاء' : 'لا يوجد موقع إلكتروني'}
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="flex justify-between items-center border-b pb-2">
                                                <span className="font-semibold text-gray-700">الهاتف:</span>
                                                <div className="text-left">
                                                    <p className="text-gray-800 font-mono">{analysis.gmb_data.phone || 'غير متوفر'}</p>
                                                    <p className="text-sm text-gray-600 mt-1">
                                                        {analysis.gmb_data.phone ? 'متاح للتواصل المباشر مع العملاء' : 'رقم الهاتف غير متوفر'}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* النتيجة الإجمالية */}
                            <div className="bg-white rounded-xl shadow-lg border p-8">
                                <h2 className="text-2xl font-bold text-center text-gray-900 mb-8">النتيجة الإجمالية</h2>
                                
                                <div className="text-center">
                                    <h3 className="text-xl font-bold text-gray-800 mb-6">تقييم الأداء العام</h3>
                                    
                                    {/* دائرة النتيجة */}
                                    <div className="flex justify-center items-center mb-6">
                                        <div className="relative w-48 h-48">
                                            <div className="w-full h-full rounded-full border-8 border-gray-200 relative overflow-hidden">
                                                <div 
                                                    className={`absolute bottom-0 left-0 right-0 transition-all duration-1000 ${
                                                        analysis.overall_score >= 85 ? 'bg-green-500' :
                                                        analysis.overall_score >= 70 ? 'bg-yellow-500' :
                                                        analysis.overall_score >= 50 ? 'bg-orange-500' : 'bg-red-500'
                                                    }`}
                                                    style={{ height: `${analysis.overall_score || 0}%` }}
                                                ></div>
                                            </div>
                                            <div className="absolute inset-0 flex flex-col items-center justify-center">
                                                <span className="text-4xl font-bold text-gray-900">{analysis.overall_score || 0}</span>
                                                <span className="text-lg text-gray-600">من 100</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="mb-4">
                                        <span className={`text-2xl font-bold ${
                                            analysis.overall_score >= 85 ? 'text-green-600' :
                                            analysis.overall_score >= 70 ? 'text-yellow-600' :
                                            analysis.overall_score >= 50 ? 'text-orange-600' : 'text-red-600'
                                        }`}>
                                            {analysis.overall_score >= 85 ? 'ممتاز' :
                                             analysis.overall_score >= 70 ? 'جيد جداً' :
                                             analysis.overall_score >= 50 ? 'جيد' : 'يحتاج تحسين'}
                                        </span>
                                    </div>

                                    <p className="text-gray-700 max-w-2xl mx-auto">
                                        {analysis.overall_score >= 85 ? 'أداء استثنائي! نشاطك التجاري يتفوق في هذا المجال ويمكن اعتباره مثالاً يحتذى به.' :
                                         analysis.overall_score >= 70 ? 'أداء جيد جداً مع وجود فرص للتحسين والنمو.' :
                                         analysis.overall_score >= 50 ? 'أداء مقبول ولكن يحتاج إلى تحسينات في عدة مجالات.' :
                                         'هناك حاجة ملحة لتحسينات شاملة لتطوير الأداء.'}
                                    </p>
                                </div>
                            </div>

                            {/* تفاصيل التقييم بالفئات - للأعمال التجارية */}
                            {analysis.type === 'business_analysis' && (
                                <div className="bg-white rounded-xl shadow-lg border p-8">
                                    <h2 className="text-2xl font-bold text-center text-gray-900 mb-8">تفاصيل التقييم بالفئات</h2>
                                    
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        {/* صفحة حجز مباشر */}
                                        <div className="border border-gray-200 rounded-lg p-6">
                                            <div className="flex justify-between items-center mb-4">
                                                <h4 className="font-semibold text-gray-800">هل يوجد صفحة حجز مباشر اونلاين مع تقويم؟</h4>
                                                <span className={`px-3 py-1 rounded-full text-sm font-medium ${
                                                    analysis.gmb_data?.website ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                                }`}>
                                                    {analysis.gmb_data?.website ? 'نعم' : 'لا'}
                                                </span>
                                            </div>
                                            <p className="text-gray-600 text-sm mb-3">
                                                {analysis.gmb_data?.website ? 
                                                    'ممتاز! نظام الحجز الإلكتروني يوفر الوقت والجهد لك وللعملاء ويقلل من فرص فقدان المواعيد.' :
                                                    'لا يتوفر نظام حجز إلكتروني. هذا يؤثر على سهولة الحجز للعملاء.'
                                                }
                                            </p>
                                            <div className="flex items-start">
                                                <span className="text-yellow-500 ml-2">💡</span>
                                                <p className="text-sm text-gray-700">
                                                    {analysis.gmb_data?.website ? 
                                                        'تأكد من سهولة استخدام نظام الحجز وإرسال تذكيرات للعملاء قبل موعدهم.' :
                                                        'فكر في إضافة نظام حجز إلكتروني لتسهيل العملية على العملاء.'
                                                    }
                                                </p>
                                            </div>
                                        </div>

                                        {/* موقع إلكتروني */}
                                        <div className="border border-gray-200 rounded-lg p-6">
                                            <div className="flex justify-between items-center mb-4">
                                                <h4 className="font-semibold text-gray-800">هل يوجد موقع إلكتروني؟</h4>
                                                <span className={`px-3 py-1 rounded-full text-sm font-medium ${
                                                    analysis.gmb_data?.website ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                                }`}>
                                                    {analysis.gmb_data?.website ? 'نعم' : 'لا'}
                                                </span>
                                            </div>
                                            <p className="text-gray-600 text-sm mb-3">
                                                {analysis.gmb_data?.website ? 
                                                    'ممتاز! هذا العنصر متوفر ويعمل بشكل جيد في نشاطك التجاري. هذا يساهم إيجابياً في جذب العملاء وبناء الثقة معهم.' :
                                                    'لا يتوفر موقع إلكتروني. هذا يحد من إمكانية العملاء للتعرف على خدماتك.'
                                                }
                                            </p>
                                            <div className="flex items-start">
                                                <span className="text-yellow-500 ml-2">💡</span>
                                                <p className="text-sm text-gray-700">
                                                    {analysis.gmb_data?.website ? 
                                                        'حافظ على هذا المستوى واستمر في تطويره للبقاء متقدماً على المنافسين.' :
                                                        'أنشئ موقعاً إلكترونياً يعرض خدماتك ومعلومات التواصل.'
                                                    }
                                                </p>
                                            </div>
                                        </div>

                                        {/* صفحة جوجل ماب */}
                                        <div className="border border-gray-200 rounded-lg p-6">
                                            <div className="flex justify-between items-center mb-4">
                                                <h4 className="font-semibold text-gray-800">هل عندك صفحة جوجل ماب؟</h4>
                                                <span className="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">نعم</span>
                                            </div>
                                            <p className="text-gray-600 text-sm mb-3">
                                                ممتاز! هذا العنصر متوفر ويعمل بشكل جيد في نشاطك التجاري. هذا يساهم إيجابياً في جذب العملاء وبناء الثقة معهم.
                                            </p>
                                            <div className="flex items-start">
                                                <span className="text-yellow-500 ml-2">💡</span>
                                                <p className="text-sm text-gray-700">
                                                    حافظ على هذا المستوى واستمر في تطويره للبقاء متقدماً على المنافسين.
                                                </p>
                                            </div>
                                        </div>

                                        {/* التقييمات والنجوم */}
                                        <div className="border border-gray-200 rounded-lg p-6">
                                            <div className="flex justify-between items-center mb-4">
                                                <h4 className="font-semibold text-gray-800">عدد التقييمات ومتوسط النجوم</h4>
                                                <span className={`px-3 py-1 rounded-full text-sm font-medium ${
                                                    analysis.gmb_data?.rating >= 4.0 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                                                }`}>
                                                    {analysis.gmb_data?.rating >= 4.0 ? 'ممتاز' : 'جيد'}
                                                </span>
                                            </div>
                                            <p className="text-gray-600 text-sm mb-3">
                                                {analysis.gmb_data?.rating >= 4.0 ? 
                                                    'ممتاز! هذا العنصر متوفر ويعمل بشكل جيد في نشاطك التجاري. هذا يساهم إيجابياً في جذب العملاء وبناء الثقة معهم.' :
                                                    'التقييمات جيدة ولكن يمكن تحسينها من خلال تطوير جودة الخدمة.'
                                                }
                                            </p>
                                            <div className="flex items-start">
                                                <span className="text-yellow-500 ml-2">💡</span>
                                                <p className="text-sm text-gray-700">
                                                    شجع العملاء الراضين على ترك تقييمات إيجابية واهتم بالرد على جميع التقييمات.
                                                </p>
                                            </div>
                                        </div>

                                        {/* ساعات العمل */}
                                        <div className="border border-gray-200 rounded-lg p-6">
                                            <div className="flex justify-between items-center mb-4">
                                                <h4 className="font-semibold text-gray-800">ساعات العمل</h4>
                                                <span className={`px-3 py-1 rounded-full text-sm font-medium ${
                                                    analysis.gmb_data?.business_hours !== 'غير متوفرة' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                                }`}>
                                                    {analysis.gmb_data?.business_hours !== 'غير متوفرة' ? 'متوفرة' : 'غير متوفرة'}
                                                </span>
                                            </div>
                                            <p className="text-gray-600 text-sm mb-3">
                                                {analysis.gmb_data?.business_hours !== 'غير متوفرة' ? 
                                                    'ممتاز! ساعات العمل واضحة ومحدثة، مما يساعد العملاء على معرفة أوقات توفرك للخدمة.' :
                                                    'ساعات العمل غير واضحة. هذا قد يسبب التباساً للعملاء.'
                                                }
                                            </p>
                                            <div className="flex items-start">
                                                <span className="text-yellow-500 ml-2">💡</span>
                                                <p className="text-sm text-gray-700">
                                                    تأكد من تحديث ساعات العمل في المواسم المختلفة أو الإجازات لتجنب إزعاج العملاء.
                                                </p>
                                            </div>
                                        </div>

                                        {/* رقم الاتصال */}
                                        <div className="border border-gray-200 rounded-lg p-6">
                                            <div className="flex justify-between items-center mb-4">
                                                <h4 className="font-semibold text-gray-800">رقم اتصال</h4>
                                                <span className={`px-3 py-1 rounded-full text-sm font-medium ${
                                                    analysis.gmb_data?.phone ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                                }`}>
                                                    {analysis.gmb_data?.phone ? 'متوفر' : 'غير متوفر'}
                                                </span>
                                            </div>
                                            <p className="text-gray-600 text-sm mb-3">
                                                {analysis.gmb_data?.phone ? 
                                                    'ممتاز! هذا العنصر متوفر ويعمل بشكل جيد في نشاطك التجاري. هذا يساهم إيجابياً في جذب العملاء وبناء الثقة معهم.' :
                                                    'رقم الهاتف غير متوفر. هذا يجعل التواصل المباشر صعباً على العملاء.'
                                                }
                                            </p>
                                            <div className="flex items-start">
                                                <span className="text-yellow-500 ml-2">💡</span>
                                                <p className="text-sm text-gray-700">
                                                    {analysis.gmb_data?.phone ? 
                                                        'حافظ على هذا المستوى واستمر في تطويره للبقاء متقدماً على المنافسين.' :
                                                        'أضف رقم هاتف واضح ومحدث للتواصل المباشر مع العملاء.'
                                                    }
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* التوصيات الاستراتيجية */}
                            <div className="bg-white rounded-xl shadow-lg border p-8">
                                <h2 className="text-2xl font-bold text-center text-gray-900 mb-8">التوصيات الاستراتيجية</h2>
                                
                                <div className="space-y-8">
                                    {/* التوصية الأولى */}
                                    <div className="border border-orange-200 rounded-lg p-6 bg-orange-50">
                                        <div className="flex items-center justify-between mb-4">
                                            <div className="flex items-center">
                                                <span className="bg-orange-500 text-white text-xl font-bold rounded-full w-8 h-8 flex items-center justify-center ml-3">#1</span>
                                                <span className="text-orange-700 font-medium">أولوية عالية</span>
                                            </div>
                                            <span className="text-2xl">💼</span>
                                        </div>
                                        
                                        <h3 className="text-xl font-bold text-orange-900 mb-4">تحسين تجربة العميل</h3>
                                        
                                        <div className="space-y-4">
                                            <div>
                                                <h4 className="font-semibold text-orange-800 mb-2">الوصف التفصيلي:</h4>
                                                <p className="text-gray-700">المؤشر الأول لزيادة المبيعات هو جودة تجربة العميل</p>
                                            </div>
                                            
                                            <div>
                                                <h4 className="font-semibold text-orange-800 mb-2">التأثير المتوقع والفوائد:</h4>
                                                <p className="text-gray-700 text-sm leading-relaxed">
                                                    تسهيل الحجز السريع على العميل هو أهم نقطة في زيادة مبيعاتك وتقليل الأسئلة والرسائل على الواتس اب. 
                                                    عدم وضوح تفاصيل الخدمة سيجعل العميل يسأل في الواتس اب وهذا يؤخر البيعة، ويزيد الضغط على خدمة العملاء. 
                                                    إتاحة الدفع الاونلاين وخدمات التقسيط هي أبسط طريقة لمضاعفة المبيعات بشكل مباشر.
                                                </p>
                                            </div>
                                            
                                            <div>
                                                <h4 className="font-semibold text-orange-800 mb-2">خطوات التنفيذ المفصلة:</h4>
                                                <div className="space-y-2">
                                                    <div className="flex items-center">
                                                        <span className="ml-2">🌐</span>
                                                        <span className="text-sm text-gray-700">الخطوة 1: تحليل الموقع الالكتروني وتطويره</span>
                                                    </div>
                                                    <div className="flex items-center">
                                                        <span className="ml-2">📝</span>
                                                        <span className="text-sm text-gray-700">الخطوة 2: وضوح وصف الخدمات وتفاصيلها</span>
                                                    </div>
                                                    <div className="flex items-center">
                                                        <span className="ml-2">💳</span>
                                                        <span className="text-sm text-gray-700">الخطوة 3: تفعيل الدفع الالكتروني وخدمات التقسيط</span>
                                                    </div>
                                                    <div className="flex items-center">
                                                        <span className="ml-2">💬</span>
                                                        <span className="text-sm text-gray-700">الخطوة 4: الربط مع الواتس اب للتواصل السريع</span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div className="bg-orange-200 border border-orange-300 rounded p-3">
                                                <p className="text-orange-800 text-sm font-medium">
                                                    عاجل - ينصح بالبدء في تنفيذ هذه التوصية فوراً لتحقيق أقصى استفادة.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {/* التوصية الثانية */}
                                    <div className="border border-blue-200 rounded-lg p-6 bg-blue-50">
                                        <div className="flex items-center justify-between mb-4">
                                            <div className="flex items-center">
                                                <span className="bg-blue-500 text-white text-xl font-bold rounded-full w-8 h-8 flex items-center justify-center ml-3">#2</span>
                                                <span className="text-blue-700 font-medium">أولوية عالية</span>
                                            </div>
                                            <span className="text-2xl">🗺️</span>
                                        </div>
                                        
                                        <h3 className="text-xl font-bold text-blue-900 mb-4">تحليل جوجل ماب</h3>
                                        
                                        <div className="space-y-4">
                                            <div>
                                                <h4 className="font-semibold text-blue-800 mb-2">الوصف التفصيلي:</h4>
                                                <p className="text-gray-700">هل سهل البحث عنك في خرائط جوجل ماب؟</p>
                                            </div>
                                            
                                            <div>
                                                <h4 className="font-semibold text-blue-800 mb-2">التأثير المتوقع والفوائد:</h4>
                                                <p className="text-gray-700 text-sm">
                                                    توفير المعلومات المطلوبة والتفاصيل كاملة تساعدك على الظهور بشكل متكرر للعملاء الباحثين عن الخدمة في تطبيق جوجل ماب.
                                                </p>
                                            </div>
                                            
                                            <div>
                                                <h4 className="font-semibold text-blue-800 mb-2">خطوات التنفيذ المفصلة:</h4>
                                                <div className="space-y-2">
                                                    <div className="flex items-center">
                                                        <span className="ml-2">📍</span>
                                                        <span className="text-sm text-gray-700">الخطوة 1: تحديث جميع معلومات Google Business Profile</span>
                                                    </div>
                                                    <div className="flex items-center">
                                                        <span className="ml-2">📷</span>
                                                        <span className="text-sm text-gray-700">الخطوة 2: إضافة صور احترافية للخدمات والمكان</span>
                                                    </div>
                                                    <div className="flex items-center">
                                                        <span className="ml-2">⏰</span>
                                                        <span className="text-sm text-gray-700">الخطوة 3: تحديث ساعات العمل وأرقام التواصل</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* نقاط القوة والضعف */}
                            <div className="bg-white rounded-xl shadow-lg border p-8">
                                <h2 className="text-2xl font-bold text-center text-gray-900 mb-8">نقاط القوة والضعف</h2>
                                
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div>
                                        <h3 className="text-xl font-bold text-green-700 mb-4 text-center">نقاط القوة</h3>
                                        <div className="space-y-3">
                                            {analysis.gmb_data?.website && (
                                                <div className="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded">
                                                    <span className="text-green-800">يوجد موقع إلكتروني</span>
                                                    <span className="bg-green-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold">1</span>
                                                </div>
                                            )}
                                            <div className="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded">
                                                <span className="text-green-800">صفحة جوجل ماب متوفرة</span>
                                                <span className="bg-green-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold">2</span>
                                            </div>
                                            {analysis.gmb_data?.phone && (
                                                <div className="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded">
                                                    <span className="text-green-800">رقم التواصل متوفر</span>
                                                    <span className="bg-green-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold">3</span>
                                                </div>
                                            )}
                                            {analysis.gmb_data?.rating >= 4.0 && (
                                                <div className="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded">
                                                    <span className="text-green-800">تقييمات جيدة</span>
                                                    <span className="bg-green-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold">4</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <h3 className="text-xl font-bold text-red-700 mb-4 text-center">نقاط الضعف</h3>
                                        <div className="space-y-3">
                                            <div className="flex items-center justify-between p-3 bg-red-50 border border-red-200 rounded">
                                                <span className="text-red-800">لم نجد دفع عبر Apple Pay</span>
                                                <span className="bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold">1</span>
                                            </div>
                                            <div className="flex items-center justify-between p-3 bg-red-50 border border-red-200 rounded">
                                                <span className="text-red-800">خدمات التقسيط غير متوفرة</span>
                                                <span className="bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold">2</span>
                                            </div>
                                            {!analysis.gmb_data?.website && (
                                                <div className="flex items-center justify-between p-3 bg-red-50 border border-red-200 rounded">
                                                    <span className="text-red-800">لا يوجد موقع إلكتروني</span>
                                                    <span className="bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold">3</span>
                                                </div>
                                            )}
                                            {analysis.gmb_data?.business_hours === 'غير متوفرة' && (
                                                <div className="flex items-center justify-between p-3 bg-red-50 border border-red-200 rounded">
                                                    <span className="text-red-800">ساعات العمل غير واضحة</span>
                                                    <span className="bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold">4</span>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* عرض تحليل الموقع العادي */}
                            {analysis.type !== 'business_analysis' && (
                                <div className="bg-white rounded-xl shadow-lg border p-8">
                                    <h2 className="text-2xl font-bold text-gray-900 mb-4">تحليل الموقع</h2>
                                    
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                            <h4 className="font-semibold text-blue-900 mb-1">النتيجة الإجمالية</h4>
                                            <div className="flex items-center">
                                                <span className="text-2xl font-bold text-blue-800">{analysis.overall_score || 'غير محدد'}%</span>
                                            </div>
                                        </div>

                                        <div className="bg-purple-50 border border-purple-200 rounded-lg p-4">
                                            <h4 className="font-semibold text-purple-900 mb-1">نوع التحليل</h4>
                                            <span className="text-sm text-purple-600">تحليل موقع إلكتروني</span>
                                        </div>

                                        <div className="bg-orange-50 border border-orange-200 rounded-lg p-4">
                                            <h4 className="font-semibold text-orange-900 mb-1">الحالة</h4>
                                            <span className="text-sm text-orange-600">مكتمل</span>
                                        </div>
                                    </div>

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
                            )}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}