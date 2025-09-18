import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function AnalyzerDropidea({ auth, recent_analyses }) {
    const [url, setUrl] = useState('');
    const [businessName, setBusinessName] = useState('');
    const [analysisType, setAnalysisType] = useState('website');
    const [isAnalyzing, setIsAnalyzing] = useState(false);
    const [analysisResults, setAnalysisResults] = useState(null);
    const [searchResults, setSearchResults] = useState([]);
    const [selectedBusiness, setSelectedBusiness] = useState(null);
    const [activeTab, setActiveTab] = useState('input');

    const analyzeWebsite = async () => {
        if (!url.trim()) {
            alert('يرجى إدخال رابط الموقع');
            return;
        }

        setIsAnalyzing(true);
        setAnalysisResults(null);

        try {
            const response = await fetch('/dropidea/analyze', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    url: url,
                    business_name: businessName,
                    analysis_type: analysisType
                })
            });

            const data = await response.json();

            if (data.success) {
                setAnalysisResults(data.data);
                setActiveTab('results');
            } else {
                alert('فشل التحليل: ' + (data.message || 'خطأ غير محدد'));
            }
        } catch (error) {
            console.error('خطأ في التحليل:', error);
            alert('حدث خطأ أثناء التحليل');
        } finally {
            setIsAnalyzing(false);
        }
    };

    const searchBusinesses = async () => {
        if (!businessName.trim()) {
            alert('يرجى إدخال اسم العمل');
            return;
        }

        try {
            const response = await fetch('/dropidea/search-business', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    business_name: businessName
                })
            });

            const data = await response.json();

            if (data.success) {
                setSearchResults(data.businesses || []);
            } else {
                alert('لم يتم العثور على أعمال');
                setSearchResults([]);
            }
        } catch (error) {
            console.error('خطأ في البحث:', error);
            alert('حدث خطأ أثناء البحث');
        }
    };

    const selectBusiness = (business) => {
        setSelectedBusiness(business);
        setUrl(business.website || '');
        setBusinessName(business.name);
    };

    const ScoreCircle = ({ score, title, color = 'blue' }) => {
        const circumference = 2 * Math.PI * 45;
        const offset = circumference - (score / 100) * circumference;
        
        const colorClasses = {
            blue: 'stroke-blue-500',
            green: 'stroke-green-500',
            yellow: 'stroke-yellow-500',
            red: 'stroke-red-500',
            purple: 'stroke-purple-500',
            indigo: 'stroke-indigo-500'
        };

        return (
            <div className="flex flex-col items-center">
                <div className="relative">
                    <svg className="w-20 h-20 transform -rotate-90" viewBox="0 0 100 100">
                        <circle
                            cx="50"
                            cy="50"
                            r="45"
                            stroke="currentColor"
                            strokeWidth="6"
                            fill="transparent"
                            className="text-gray-200"
                        />
                        <circle
                            cx="50"
                            cy="50"
                            r="45"
                            stroke="currentColor"
                            strokeWidth="6"
                            fill="transparent"
                            strokeDasharray={circumference}
                            strokeDashoffset={offset}
                            className={colorClasses[color]}
                            strokeLinecap="round"
                        />
                    </svg>
                    <div className="absolute inset-0 flex items-center justify-center">
                        <span className="text-lg font-bold text-gray-900">{score || 0}</span>
                    </div>
                </div>
                <span className="mt-2 text-sm font-medium text-gray-700 text-center">{title}</span>
            </div>
        );
    };

    const ResultsSection = ({ results }) => {
        const [activeResultTab, setActiveResultTab] = useState('overview');

        const getScoreColor = (score) => {
            if (score >= 90) return 'green';
            if (score >= 80) return 'blue';
            if (score >= 70) return 'yellow';
            if (score >= 60) return 'orange';
            return 'red';
        };

        return (
            <div className="space-y-6">
                {/* العنوان والمعلومات الأساسية */}
                <div className="bg-white rounded-lg shadow-sm border p-6">
                    <h2 className="text-2xl font-bold text-gray-900 mb-4">
                        نتائج تحليل {results.basic_info.domain}
                    </h2>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
                        <div>
                            <span className="font-medium">الرابط:</span> {results.basic_info.url}
                        </div>
                        <div>
                            <span className="font-medium">النطاق:</span> {results.basic_info.domain}
                        </div>
                        <div>
                            <span className="font-medium">وقت التحليل:</span> {results.basic_info.analysis_time}
                        </div>
                    </div>
                </div>

                {/* النتائج الإجمالية */}
                <div className="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg shadow-sm border p-6">
                    <h3 className="text-xl font-bold text-gray-900 mb-6 text-center">النتيجة الإجمالية والتفصيلية</h3>
                    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 justify-items-center">
                        <ScoreCircle 
                            score={results.scores.overall} 
                            title="النتيجة الإجمالية" 
                            color={getScoreColor(results.scores.overall)} 
                        />
                        <ScoreCircle 
                            score={results.scores.performance} 
                            title="الأداء" 
                            color={getScoreColor(results.scores.performance)} 
                        />
                        <ScoreCircle 
                            score={results.scores.security} 
                            title="الأمان" 
                            color={getScoreColor(results.scores.security)} 
                        />
                        <ScoreCircle 
                            score={results.scores.seo} 
                            title="SEO" 
                            color={getScoreColor(results.scores.seo)} 
                        />
                        <ScoreCircle 
                            score={results.scores.ux} 
                            title="UX" 
                            color={getScoreColor(results.scores.ux)} 
                        />
                        <ScoreCircle 
                            score={results.scores.maps_presence} 
                            title="Google Maps" 
                            color={getScoreColor(results.scores.maps_presence)} 
                        />
                    </div>
                </div>

                {/* تبويبات التفاصيل */}
                <div className="bg-white rounded-lg shadow-sm border">
                    <div className="border-b border-gray-200">
                        <nav className="-mb-px flex flex-wrap">
                            {[
                                { id: 'overview', name: 'نظرة عامة', icon: '📊' },
                                { id: 'performance', name: 'الأداء', icon: '⚡' },
                                { id: 'security', name: 'الأمان', icon: '🔒' },
                                { id: 'technologies', name: 'التقنيات', icon: '🛠️' },
                                { id: 'seo', name: 'SEO', icon: '📈' },
                                { id: 'ai', name: 'الذكاء الاصطناعي', icon: '🤖' }
                            ].map(tab => (
                                <button
                                    key={tab.id}
                                    onClick={() => setActiveResultTab(tab.id)}
                                    className={`px-4 py-3 text-sm font-medium border-b-2 ${
                                        activeResultTab === tab.id
                                            ? 'border-blue-500 text-blue-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    }`}
                                >
                                    <span className="mr-2">{tab.icon}</span>
                                    {tab.name}
                                </button>
                            ))}
                        </nav>
                    </div>

                    <div className="p-6">
                        {activeResultTab === 'overview' && (
                            <div className="space-y-6">
                                <h4 className="text-lg font-semibold text-gray-900">التوصيات الذكية</h4>
                                {results.recommendations && results.recommendations.length > 0 ? (
                                    <div className="grid gap-4">
                                        {results.recommendations.map((rec, index) => (
                                            <div key={index} className={`p-4 rounded-lg border-l-4 ${
                                                rec.priority === 'critical' ? 'border-red-500 bg-red-50' :
                                                rec.priority === 'high' ? 'border-orange-500 bg-orange-50' :
                                                'border-blue-500 bg-blue-50'
                                            }`}>
                                                <div className="flex items-start">
                                                    <div className="flex-1">
                                                        <h5 className="font-medium text-gray-900">{rec.title}</h5>
                                                        <p className="text-sm text-gray-600 mt-1">{rec.description}</p>
                                                        <span className={`inline-block mt-2 px-2 py-1 text-xs rounded-full ${
                                                            rec.priority === 'critical' ? 'bg-red-100 text-red-800' :
                                                            rec.priority === 'high' ? 'bg-orange-100 text-orange-800' :
                                                            'bg-blue-100 text-blue-800'
                                                        }`}>
                                                            {rec.priority === 'critical' ? 'حرج' : 
                                                             rec.priority === 'high' ? 'عالي' : 'متوسط'}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-gray-500 text-center py-8">لا توجد توصيات حالياً - الموقع في حالة جيدة! ✨</p>
                                )}
                            </div>
                        )}

                        {activeResultTab === 'performance' && (
                            <div className="space-y-6">
                                <h4 className="text-lg font-semibold text-gray-900">تحليل الأداء المتقدم</h4>
                                
                                {/* نتائج PageSpeed */}
                                <div className="grid md:grid-cols-2 gap-4">
                                    <div className="bg-gray-50 rounded-lg p-4">
                                        <h5 className="font-medium text-gray-900 mb-2">📱 الهاتف المحمول</h5>
                                        <div className="text-3xl font-bold text-blue-600">
                                            {results.performance.mobile_score || 'N/A'}
                                        </div>
                                        <p className="text-sm text-gray-600">نقاط PageSpeed</p>
                                    </div>
                                    <div className="bg-gray-50 rounded-lg p-4">
                                        <h5 className="font-medium text-gray-900 mb-2">💻 سطح المكتب</h5>
                                        <div className="text-3xl font-bold text-green-600">
                                            {results.performance.desktop_score || 'N/A'}
                                        </div>
                                        <p className="text-sm text-gray-600">نقاط PageSpeed</p>
                                    </div>
                                </div>

                                {/* Core Web Vitals */}
                                {results.performance.core_web_vitals && Object.keys(results.performance.core_web_vitals).length > 0 && (
                                    <div>
                                        <h5 className="font-medium text-gray-900 mb-3">Core Web Vitals</h5>
                                        <div className="grid md:grid-cols-3 gap-4">
                                            {Object.entries(results.performance.core_web_vitals).map(([key, value]) => (
                                                <div key={key} className="bg-gray-50 rounded-lg p-4">
                                                    <div className="text-sm text-gray-600 uppercase tracking-wide">{key}</div>
                                                    <div className="text-xl font-bold text-gray-900 mt-1">
                                                        {value?.displayValue || 'N/A'}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {/* Lighthouse Scores */}
                                {results.performance.lighthouse_scores && (
                                    <div>
                                        <h5 className="font-medium text-gray-900 mb-3">نقاط Lighthouse</h5>
                                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                                            {Object.entries(results.performance.lighthouse_scores).map(([key, value]) => (
                                                <ScoreCircle 
                                                    key={key}
                                                    score={value} 
                                                    title={key === 'performance' ? 'الأداء' : 
                                                           key === 'seo' ? 'SEO' : 
                                                           key === 'accessibility' ? 'الإمكانية' : 
                                                           'أفضل الممارسات'} 
                                                    color={getScoreColor(value)}
                                                />
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                        {activeResultTab === 'security' && (
                            <div className="space-y-6">
                                <h4 className="text-lg font-semibold text-gray-900">تحليل الأمان الشامل</h4>
                                
                                {/* SSL Analysis */}
                                {results.security.ssl_analysis && (
                                    <div className="bg-gray-50 rounded-lg p-4">
                                        <h5 className="font-medium text-gray-900 mb-3">🔒 تحليل SSL/TLS</h5>
                                        <div className="grid md:grid-cols-2 gap-4">
                                            <div>
                                                <span className="text-sm text-gray-600">حالة SSL:</span>
                                                <span className={`ml-2 px-2 py-1 text-xs rounded-full ${
                                                    results.security.ssl_analysis.has_ssl 
                                                        ? 'bg-green-100 text-green-800' 
                                                        : 'bg-red-100 text-red-800'
                                                }`}>
                                                    {results.security.ssl_analysis.has_ssl ? 'مُفعل' : 'غير مُفعل'}
                                                </span>
                                            </div>
                                            {results.security.ssl_analysis.ssl_grade && (
                                                <div>
                                                    <span className="text-sm text-gray-600">تقييم SSL:</span>
                                                    <span className="ml-2 font-bold text-blue-600">
                                                        {results.security.ssl_analysis.ssl_grade}
                                                    </span>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}

                                {/* Security Headers */}
                                {results.security.security_headers && Object.keys(results.security.security_headers).length > 0 && (
                                    <div>
                                        <h5 className="font-medium text-gray-900 mb-3">🛡️ Headers الأمان</h5>
                                        <div className="grid gap-3">
                                            {Object.entries(results.security.security_headers).map(([header, data]) => (
                                                <div key={header} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                                    <span className="font-medium text-gray-900 capitalize">{header.replace('_', ' ')}</span>
                                                    <span className={`px-2 py-1 text-xs rounded-full ${
                                                        data.present 
                                                            ? 'bg-green-100 text-green-800' 
                                                            : 'bg-red-100 text-red-800'
                                                    }`}>
                                                        {data.present ? 'موجود' : 'غير موجود'}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                        {activeResultTab === 'technologies' && (
                            <div className="space-y-6">
                                <h4 className="text-lg font-semibold text-gray-900">التقنيات المكتشفة</h4>
                                
                                {results.technologies && Object.keys(results.technologies).length > 0 ? (
                                    <div className="grid gap-6">
                                        {Object.entries(results.technologies).map(([category, techs]) => (
                                            techs && techs.length > 0 && (
                                                <div key={category} className="bg-gray-50 rounded-lg p-4">
                                                    <h5 className="font-medium text-gray-900 mb-3 capitalize">
                                                        {category.replace('_', ' ')}
                                                    </h5>
                                                    <div className="flex flex-wrap gap-2">
                                                        {techs.map((tech, index) => (
                                                            <span key={index} className="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full">
                                                                {tech.name} {tech.version && `v${tech.version}`}
                                                            </span>
                                                        ))}
                                                    </div>
                                                </div>
                                            )
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-gray-500 text-center py-8">لم يتم كشف تقنيات محددة</p>
                                )}
                            </div>
                        )}

                        {activeResultTab === 'seo' && (
                            <div className="space-y-6">
                                <h4 className="text-lg font-semibold text-gray-900">تحليل SEO المتقدم</h4>
                                
                                {results.seo.metadata && (
                                    <div className="space-y-4">
                                        <div className="bg-gray-50 rounded-lg p-4">
                                            <h5 className="font-medium text-gray-900 mb-2">📝 العنوان والوصف</h5>
                                            <div className="space-y-2">
                                                <div>
                                                    <span className="text-sm text-gray-600">العنوان:</span>
                                                    <p className="mt-1 text-gray-900">{results.seo.metadata.title || 'غير محدد'}</p>
                                                </div>
                                                <div>
                                                    <span className="text-sm text-gray-600">الوصف:</span>
                                                    <p className="mt-1 text-gray-900">{results.seo.metadata.description || 'غير محدد'}</p>
                                                </div>
                                            </div>
                                        </div>

                                        {results.seo.metadata.h1_tags && results.seo.metadata.h1_tags.length > 0 && (
                                            <div className="bg-gray-50 rounded-lg p-4">
                                                <h5 className="font-medium text-gray-900 mb-2">🏷️ عناوين H1</h5>
                                                <ul className="list-disc list-inside space-y-1">
                                                    {results.seo.metadata.h1_tags.map((tag, index) => (
                                                        <li key={index} className="text-gray-700">{tag}</li>
                                                    ))}
                                                </ul>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                        )}

                        {activeResultTab === 'ai' && (
                            <div className="space-y-6">
                                <h4 className="text-lg font-semibold text-gray-900">تحليل الذكاء الاصطناعي</h4>
                                
                                {results.ai_insights ? (
                                    <div className="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6">
                                        <div className="prose prose-sm max-w-none">
                                            <div className="whitespace-pre-wrap text-gray-800">
                                                {results.ai_insights}
                                            </div>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="text-center py-8">
                                        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
                                        <p className="mt-4 text-gray-500">جاري تحليل البيانات بالذكاء الاصطناعي...</p>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        );
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="AnalyzerDropidea - محلل المواقع المتقدم" />

            <div className="py-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-8">
                        <h1 className="text-3xl font-bold text-gray-900 mb-2">
                            🚀 AnalyzerDropidea
                        </h1>
                        <p className="text-lg text-gray-600">
                            محلل المواقع الشامل مع تكامل الذكاء الاصطناعي والخرائط
                        </p>
                    </div>

                    <div className="bg-white rounded-lg shadow-sm border mb-6">
                        <div className="border-b border-gray-200">
                            <nav className="-mb-px flex">
                                <button
                                    onClick={() => setActiveTab('input')}
                                    className={`px-6 py-4 text-sm font-medium border-b-2 ${
                                        activeTab === 'input'
                                            ? 'border-blue-500 text-blue-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    }`}
                                >
                                    📊 تحليل جديد
                                </button>
                                <button
                                    onClick={() => setActiveTab('history')}
                                    className={`px-6 py-4 text-sm font-medium border-b-2 ${
                                        activeTab === 'history'
                                            ? 'border-blue-500 text-blue-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    }`}
                                >
                                    📚 التحليلات السابقة
                                </button>
                                {analysisResults && (
                                    <button
                                        onClick={() => setActiveTab('results')}
                                        className={`px-6 py-4 text-sm font-medium border-b-2 ${
                                            activeTab === 'results'
                                                ? 'border-blue-500 text-blue-600'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                        }`}
                                    >
                                        ✨ النتائج
                                    </button>
                                )}
                            </nav>
                        </div>

                        <div className="p-6">
                            {activeTab === 'input' && (
                                <div className="space-y-6">
                                    <div className="grid md:grid-cols-2 gap-6">
                                        {/* إدخال الموقع */}
                                        <div className="bg-blue-50 rounded-lg p-6">
                                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                                🌐 تحليل موقع إلكتروني
                                            </h3>
                                            <div className="space-y-4">
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                                        رابط الموقع
                                                    </label>
                                                    <input
                                                        type="url"
                                                        value={url}
                                                        onChange={(e) => setUrl(e.target.value)}
                                                        placeholder="https://example.com"
                                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                                        disabled={isAnalyzing}
                                                    />
                                                </div>
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                                        اسم العمل (اختياري)
                                                    </label>
                                                    <input
                                                        type="text"
                                                        value={businessName}
                                                        onChange={(e) => setBusinessName(e.target.value)}
                                                        placeholder="اسم الشركة أو العمل"
                                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                                        disabled={isAnalyzing}
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        {/* البحث في Google Places */}
                                        <div className="bg-green-50 rounded-lg p-6">
                                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                                🏢 البحث عن الأعمال
                                            </h3>
                                            <div className="space-y-4">
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                                        اسم العمل
                                                    </label>
                                                    <input
                                                        type="text"
                                                        value={businessName}
                                                        onChange={(e) => setBusinessName(e.target.value)}
                                                        placeholder="مطعم الياسمين، شركة التقنية المتقدمة"
                                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500"
                                                    />
                                                </div>
                                                <button
                                                    onClick={searchBusinesses}
                                                    className="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors"
                                                >
                                                    🔍 البحث في Google Places
                                                </button>
                                            </div>
                                            
                                            {searchResults.length > 0 && (
                                                <div className="mt-4 space-y-2">
                                                    <h4 className="font-medium text-gray-900">نتائج البحث:</h4>
                                                    {searchResults.map((business, index) => (
                                                        <div
                                                            key={index}
                                                            onClick={() => selectBusiness(business)}
                                                            className="p-3 border rounded-md cursor-pointer hover:bg-green-100 transition-colors"
                                                        >
                                                            <div className="font-medium text-gray-900">{business.name}</div>
                                                            <div className="text-sm text-gray-600">{business.address}</div>
                                                            {business.rating && (
                                                                <div className="text-sm text-yellow-600">
                                                                    ⭐ {business.rating} ({business.reviews_count} مراجعة)
                                                                </div>
                                                            )}
                                                        </div>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    <div className="flex justify-center">
                                        <button
                                            onClick={analyzeWebsite}
                                            disabled={isAnalyzing || !url.trim()}
                                            className={`px-8 py-3 text-lg font-semibold rounded-lg transition-all ${
                                                isAnalyzing || !url.trim()
                                                    ? 'bg-gray-400 text-gray-200 cursor-not-allowed'
                                                    : 'bg-blue-600 text-white hover:bg-blue-700 transform hover:scale-105'
                                            }`}
                                        >
                                            {isAnalyzing ? (
                                                <>
                                                    <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white inline-block mr-2"></div>
                                                    جاري التحليل الشامل...
                                                </>
                                            ) : (
                                                '🚀 بدء التحليل الشامل'
                                            )}
                                        </button>
                                    </div>

                                    {selectedBusiness && (
                                        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                            <h4 className="font-medium text-gray-900 mb-2">العمل المحدد:</h4>
                                            <div className="text-sm text-gray-600">
                                                <div><strong>الاسم:</strong> {selectedBusiness.name}</div>
                                                <div><strong>العنوان:</strong> {selectedBusiness.address}</div>
                                                <div><strong>الموقع:</strong> {selectedBusiness.website || 'غير متاح'}</div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}

                            {activeTab === 'history' && (
                                <div>
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4">التحليلات السابقة</h3>
                                    {recent_analyses.length > 0 ? (
                                        <div className="grid gap-4">
                                            {recent_analyses.map((analysis) => (
                                                <div key={analysis.id} className="border rounded-lg p-4 hover:bg-gray-50">
                                                    <div className="flex justify-between items-start">
                                                        <div>
                                                            <h4 className="font-medium text-gray-900">{analysis.domain}</h4>
                                                            <p className="text-sm text-gray-600">
                                                                {analysis.created_at} • {analysis.analysis_time}
                                                            </p>
                                                        </div>
                                                        <div className="text-right">
                                                            <div className="text-lg font-bold text-blue-600">
                                                                {analysis.composite_score || 'N/A'}
                                                            </div>
                                                            <div className="text-xs text-gray-500">النتيجة الإجمالية</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-gray-500 text-center py-8">لا توجد تحليلات سابقة</p>
                                    )}
                                </div>
                            )}

                            {activeTab === 'results' && analysisResults && (
                                <ResultsSection results={analysisResults} />
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}