import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';

export default function WebsiteAnalyzer({ auth, analysis }) {
    const [isAnalyzing, setIsAnalyzing] = useState(false);
    const [activeTab, setActiveTab] = useState('overview');

    const { data, setData, post, processing, errors, reset } = useForm({
        url: '',
        region: 'global',
        analysis_type: 'full'
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        setIsAnalyzing(true);
        
        post(route('website.analyze'), {
            onSuccess: () => {
                setIsAnalyzing(false);
            },
            onError: () => {
                setIsAnalyzing(false);
            }
        });
    };

    const downloadPDF = () => {
        if (analysis) {
            window.open(route('website.report.pdf', { id: analysis.id }), '_blank');
        }
    };

    const ScoreCircle = ({ score, label, color = 'blue' }) => {
        const circumference = 2 * Math.PI * 45;
        const strokeDasharray = circumference;
        const strokeDashoffset = circumference - (score / 100) * circumference;

        return (
            <div className="flex flex-col items-center">
                <div className="relative w-24 h-24">
                    <svg className="w-24 h-24 transform -rotate-90" viewBox="0 0 100 100">
                        <circle
                            cx="50"
                            cy="50"
                            r="45"
                            stroke="currentColor"
                            strokeWidth="8"
                            fill="transparent"
                            className="text-gray-200"
                        />
                        <circle
                            cx="50"
                            cy="50"
                            r="45"
                            stroke="currentColor"
                            strokeWidth="8"
                            fill="transparent"
                            strokeDasharray={strokeDasharray}
                            strokeDashoffset={strokeDashoffset}
                            className={`text-${color}-500 transition-all duration-1000 ease-out`}
                        />
                    </svg>
                    <div className="absolute inset-0 flex items-center justify-center">
                        <span className="text-xl font-bold text-gray-700">{score}</span>
                    </div>
                </div>
                <span className="text-sm text-gray-600 mt-2 text-center">{label}</span>
            </div>
        );
    };

    const TechnologyBadge = ({ tech, category }) => {
        const colors = {
            frontend: 'bg-blue-100 text-blue-800',
            backend: 'bg-green-100 text-green-800',
            cms: 'bg-purple-100 text-purple-800',
            analytics: 'bg-yellow-100 text-yellow-800',
            security: 'bg-red-100 text-red-800',
            hosting: 'bg-gray-100 text-gray-800'
        };

        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colors[category] || 'bg-gray-100 text-gray-800'}`}>
                {tech}
            </span>
        );
    };

    const tabs = [
        { id: 'overview', name: 'نظرة عامة', icon: '📊' },
        { id: 'seo', name: 'تحسين محركات البحث', icon: '🔍' },
        { id: 'performance', name: 'الأداء', icon: '⚡' },
        { id: 'technologies', name: 'التقنيات', icon: '🛠️' },
        { id: 'ai-insights', name: 'تحليل الذكاء الاصطناعي', icon: '🤖' },
        { id: 'recommendations', name: 'التوصيات', icon: '💡' }
    ];

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="محلل مواقع الويب الاحترافي" />

            <div className="min-h-screen bg-gray-50">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    {/* Header */}
                    <div className="mb-8">
                        <h1 className="text-3xl font-bold text-gray-900 mb-2">محلل مواقع الويب الشامل</h1>
                        <p className="text-gray-600">احصل على تحليل مفصل لأي موقع ويب باستخدام الذكاء الاصطناعي</p>
                    </div>

                    {/* Analysis Form */}
                    <div className="bg-white rounded-xl shadow-sm border p-6 mb-8">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <InputLabel htmlFor="url" value="رابط الموقع" />
                                    <TextInput
                                        id="url"
                                        type="url"
                                        name="url"
                                        value={data.url}
                                        className="mt-1 block w-full"
                                        placeholder="https://example.com"
                                        onChange={(e) => setData('url', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.url} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="region" value="المنطقة الجغرافية" />
                                    <select
                                        id="region"
                                        name="region"
                                        value={data.region}
                                        onChange={(e) => setData('region', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    >
                                        <option value="global">عالمي</option>
                                        <option value="middle-east">الشرق الأوسط</option>
                                        <option value="gulf">دول الخليج</option>
                                        <option value="egypt">مصر</option>
                                        <option value="saudi">السعودية</option>
                                        <option value="uae">الإمارات</option>
                                    </select>
                                </div>

                                <div>
                                    <InputLabel htmlFor="analysis_type" value="نوع التحليل" />
                                    <select
                                        id="analysis_type"
                                        name="analysis_type"
                                        value={data.analysis_type}
                                        onChange={(e) => setData('analysis_type', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    >
                                        <option value="full">تحليل شامل</option>
                                        <option value="seo">السيو فقط</option>
                                        <option value="performance">الأداء فقط</option>
                                        <option value="competitors">المنافسين فقط</option>
                                    </select>
                                </div>
                            </div>

                            <div className="flex justify-center">
                                <PrimaryButton 
                                    className="px-8 py-3 text-lg" 
                                    disabled={processing || isAnalyzing}
                                >
                                    {isAnalyzing ? (
                                        <>
                                            <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            جاري التحليل...
                                        </>
                                    ) : (
                                        'بدء التحليل'
                                    )}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>

                    {/* Analysis Results */}
                    {analysis && (
                        <div className="space-y-8">
                            {/* Scores Overview */}
                            <div className="bg-white rounded-xl shadow-sm border p-6">
                                <div className="flex justify-between items-center mb-6">
                                    <h2 className="text-2xl font-bold text-gray-900">نتائج التحليل</h2>
                                    <button
                                        onClick={downloadPDF}
                                        className="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors"
                                    >
                                        <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        تحميل التقرير PDF
                                    </button>
                                </div>

                                <div className="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
                                    <ScoreCircle score={analysis.seo_score || 0} label="السيو" color="blue" />
                                    <ScoreCircle score={analysis.performance_score || 0} label="الأداء" color="green" />
                                    <ScoreCircle score={analysis.ai_score || 0} label="تقييم الذكاء الاصطناعي" color="purple" />
                                    <div className="flex flex-col items-center">
                                        <div className="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center">
                                            <span className="text-2xl font-bold text-gray-700">{analysis.load_time || 0}s</span>
                                        </div>
                                        <span className="text-sm text-gray-600 mt-2 text-center">سرعة التحميل</span>
                                    </div>
                                </div>

                                <div className="text-center">
                                    <p className="text-lg text-gray-600">تم تحليل الموقع: <span className="font-semibold text-blue-600">{analysis.url}</span></p>
                                </div>
                            </div>

                            {/* Tabs Navigation */}
                            <div className="bg-white rounded-xl shadow-sm border">
                                <div className="border-b border-gray-200">
                                    <nav className="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                                        {tabs.map((tab) => (
                                            <button
                                                key={tab.id}
                                                onClick={() => setActiveTab(tab.id)}
                                                className={`${
                                                    activeTab === tab.id
                                                        ? 'border-blue-500 text-blue-600'
                                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                                } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center`}
                                            >
                                                <span className="mr-2">{tab.icon}</span>
                                                {tab.name}
                                            </button>
                                        ))}
                                    </nav>
                                </div>

                                <div className="p-6">
                                    {/* Overview Tab */}
                                    {activeTab === 'overview' && (
                                        <div className="space-y-6">
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                {/* Strengths */}
                                                <div className="bg-green-50 rounded-lg p-4">
                                                    <h3 className="text-lg font-semibold text-green-800 mb-3 flex items-center">
                                                        <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        نقاط القوة
                                                    </h3>
                                                    <ul className="space-y-2">
                                                        {analysis.strengths?.map((strength, index) => (
                                                            <li key={index} className="text-green-700 flex items-start">
                                                                <span className="text-green-500 mr-2">•</span>
                                                                {strength}
                                                            </li>
                                                        ))}
                                                    </ul>
                                                </div>

                                                {/* Weaknesses */}
                                                <div className="bg-red-50 rounded-lg p-4">
                                                    <h3 className="text-lg font-semibold text-red-800 mb-3 flex items-center">
                                                        <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                                        </svg>
                                                        نقاط الضعف
                                                    </h3>
                                                    <ul className="space-y-2">
                                                        {analysis.weaknesses?.map((weakness, index) => (
                                                            <li key={index} className="text-red-700 flex items-start">
                                                                <span className="text-red-500 mr-2">•</span>
                                                                {weakness}
                                                            </li>
                                                        ))}
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {/* Technologies Tab */}
                                    {activeTab === 'technologies' && (
                                        <div className="space-y-6">
                                            <h3 className="text-xl font-semibold text-gray-900">التقنيات والبرمجيات المستخدمة</h3>
                                            {analysis.technologies_summary && Object.keys(analysis.technologies_summary).length > 0 ? (
                                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                                    {Object.entries(analysis.technologies_summary).map(([category, techs]) => (
                                                        <div key={category} className="bg-gray-50 rounded-lg p-4">
                                                            <h4 className="font-semibold text-gray-800 mb-3 capitalize">
                                                                {category === 'frontend' && 'واجهة المستخدم'}
                                                                {category === 'backend' && 'الخادم الخلفي'}
                                                                {category === 'cms' && 'نظام إدارة المحتوى'}
                                                                {category === 'analytics' && 'التحليلات'}
                                                                {category === 'security' && 'الأمان'}
                                                                {category === 'hosting' && 'الاستضافة'}
                                                            </h4>
                                                            <div className="flex flex-wrap gap-2">
                                                                {techs.map((tech, index) => (
                                                                    <TechnologyBadge key={index} tech={tech} category={category} />
                                                                ))}
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            ) : (
                                                <div className="text-center py-8">
                                                    <p className="text-gray-500">لم يتم العثور على معلومات تقنية مفصلة</p>
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {/* AI Insights Tab */}
                                    {activeTab === 'ai-insights' && (
                                        <div className="space-y-6">
                                            <h3 className="text-xl font-semibold text-gray-900">تحليل الذكاء الاصطناعي</h3>
                                            {analysis.ai_analysis?.summary ? (
                                                <div className="bg-blue-50 rounded-lg p-6">
                                                    <h4 className="font-semibold text-blue-800 mb-3">ملخص التحليل</h4>
                                                    <p className="text-blue-700 whitespace-pre-line">{analysis.ai_analysis.summary}</p>
                                                </div>
                                            ) : (
                                                <div className="text-center py-8">
                                                    <p className="text-gray-500">تحليل الذكاء الاصطناعي غير متاح حالياً</p>
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {/* Recommendations Tab */}
                                    {activeTab === 'recommendations' && (
                                        <div className="space-y-6">
                                            <h3 className="text-xl font-semibold text-gray-900">التوصيات والتحسينات</h3>
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                {analysis.detailed_sections?.seo_recommendations && (
                                                    <div className="bg-blue-50 rounded-lg p-4">
                                                        <h4 className="font-semibold text-blue-800 mb-3">تحسين محركات البحث</h4>
                                                        <ul className="space-y-2">
                                                            {analysis.detailed_sections.seo_recommendations.map((rec, index) => (
                                                                <li key={index} className="text-blue-700 text-sm">{rec}</li>
                                                            ))}
                                                        </ul>
                                                    </div>
                                                )}

                                                {analysis.detailed_sections?.performance_recommendations && (
                                                    <div className="bg-green-50 rounded-lg p-4">
                                                        <h4 className="font-semibold text-green-800 mb-3">تحسين الأداء</h4>
                                                        <ul className="space-y-2">
                                                            {analysis.detailed_sections.performance_recommendations.map((rec, index) => (
                                                                <li key={index} className="text-green-700 text-sm">{rec}</li>
                                                            ))}
                                                        </ul>
                                                    </div>
                                                )}

                                                {analysis.detailed_sections?.security_recommendations && (
                                                    <div className="bg-red-50 rounded-lg p-4">
                                                        <h4 className="font-semibold text-red-800 mb-3">الأمان والحماية</h4>
                                                        <ul className="space-y-2">
                                                            {analysis.detailed_sections.security_recommendations.map((rec, index) => (
                                                                <li key={index} className="text-red-700 text-sm">{rec}</li>
                                                            ))}
                                                        </ul>
                                                    </div>
                                                )}

                                                {analysis.detailed_sections?.ux_recommendations && (
                                                    <div className="bg-purple-50 rounded-lg p-4">
                                                        <h4 className="font-semibold text-purple-800 mb-3">تجربة المستخدم</h4>
                                                        <ul className="space-y-2">
                                                            {analysis.detailed_sections.ux_recommendations.map((rec, index) => (
                                                                <li key={index} className="text-purple-700 text-sm">{rec}</li>
                                                            ))}
                                                        </ul>
                                                    </div>
                                                )}
                                            </div>

                                            {/* General Recommendations */}
                                            {analysis.recommendations && analysis.recommendations.length > 0 && (
                                                <div className="bg-yellow-50 rounded-lg p-4">
                                                    <h4 className="font-semibold text-yellow-800 mb-3">توصيات عامة</h4>
                                                    <ul className="space-y-2">
                                                        {analysis.recommendations.map((rec, index) => (
                                                            <li key={index} className="text-yellow-700 flex items-start">
                                                                <span className="text-yellow-500 mr-2">💡</span>
                                                                {rec}
                                                            </li>
                                                        ))}
                                                    </ul>
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

