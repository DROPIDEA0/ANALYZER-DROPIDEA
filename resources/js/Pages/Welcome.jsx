import { Link, Head } from '@inertiajs/react';
import { useEffect } from 'react';

export default function Welcome({ auth, laravelVersion, phpVersion }) {
    // التوجيه المباشر لصفحة المحلل إذا كان المستخدم مسجل دخول
    useEffect(() => {
        if (auth.user) {
            window.location.href = '/analyzer';
        }
    }, [auth.user]);

    return (
        <>
            <Head title="محلل المواقع الاحترافي - AnalyzerDropidea" />
            <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 rtl-container font-arabic">
                {/* Header */}
                <header className="relative overflow-hidden">
                    <div className="absolute inset-0 gradient-primary opacity-90"></div>
                    <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                        <nav className="flex justify-between items-center mb-8">
                            <div className="flex items-center space-x-reverse space-x-4">
                                <div className="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-lg">
                                    <span className="text-2xl font-bold text-blue-600">🔍</span>
                                </div>
                                <div>
                                    <h1 className="text-2xl font-bold text-white heading-primary">AnalyzerDropidea</h1>
                                    <p className="text-blue-100 text-sm font-almarai">محلل المواقع الاحترافي</p>
                                </div>
                            </div>
                            
                            <div className="flex items-center space-x-reverse space-x-4">
                                {auth.user ? (
                                    <Link
                                        href={route('website.analyzer')}
                                        className="btn-arabic bg-white text-blue-600 hover:bg-blue-50 shadow-lg transform hover:scale-105"
                                    >
                                        لوحة التحكم
                                    </Link>
                                ) : (
                                    <div className="flex space-x-reverse space-x-3">
                                        <Link
                                            href={route('login')}
                                            className="btn-arabic bg-white/20 text-white border border-white/30 hover:bg-white/30"
                                        >
                                            تسجيل الدخول
                                        </Link>
                                        <Link
                                            href={route('register')}
                                            className="btn-arabic bg-white text-blue-600 hover:bg-blue-50 shadow-lg"
                                        >
                                            إنشاء حساب
                                        </Link>
                                    </div>
                                )}
                            </div>
                        </nav>
                    </div>
                </header>

                {/* Hero Section */}
                <section className="relative -mt-20 pt-32 pb-20">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center mb-16 animate-fade-in-right">
                            <h2 className="text-5xl md:text-6xl font-bold text-gray-900 mb-6 heading-primary">
                                محلل المواقع
                                <span className="block text-transparent bg-clip-text gradient-primary">الاحترافي</span>
                            </h2>
                            <p className="text-xl text-gray-600 max-w-3xl mx-auto mb-8 text-body leading-relaxed">
                                احصل على تحليل شامل ومفصل لأي موقع ويب باستخدام أحدث تقنيات الذكاء الاصطناعي. 
                                اكتشف نقاط القوة والضعف واحصل على توصيات مخصصة لتحسين موقعك.
                            </p>
                            <div className="flex flex-col sm:flex-row gap-4 justify-center items-center">
                                {auth.user ? (
                                    <Link
                                        href={route('website.analyzer')}
                                        className="btn-arabic gradient-primary text-white px-8 py-4 text-lg shadow-arabic-lg transform hover:scale-105 hover:shadow-xl transition-all duration-300"
                                    >
                                        🚀 ابدأ التحليل الآن
                                    </Link>
                                ) : (
                                    <>
                                        <Link
                                            href={route('register')}
                                            className="btn-arabic gradient-primary text-white px-8 py-4 text-lg shadow-arabic-lg transform hover:scale-105 hover:shadow-xl transition-all duration-300"
                                        >
                                            🚀 ابدأ التحليل المجاني
                                        </Link>
                                        <Link
                                            href={route('login')}
                                            className="btn-arabic bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 px-8 py-4 text-lg shadow-arabic"
                                        >
                                            تسجيل الدخول
                                        </Link>
                                    </>
                                )}
                            </div>
                        </div>

                        {/* Features Grid */}
                        <div className="grid md:grid-cols-3 gap-8 mb-20 animate-fade-in-left">
                            <div className="card-arabic p-8 text-center">
                                <div className="w-16 h-16 gradient-primary rounded-2xl flex items-center justify-center mx-auto mb-6">
                                    <span className="text-3xl">🤖</span>
                                </div>
                                <h3 className="text-xl font-bold text-gray-900 mb-4 heading-secondary">الذكاء الاصطناعي</h3>
                                <p className="text-gray-600 text-body">
                                    تحليل متقدم باستخدام 3 منصات ذكاء اصطناعي: OpenAI، Anthropic، وGoogle Gemini
                                </p>
                            </div>
                            
                            <div className="card-arabic p-8 text-center">
                                <div className="w-16 h-16 gradient-success rounded-2xl flex items-center justify-center mx-auto mb-6">
                                    <span className="text-3xl">📊</span>
                                </div>
                                <h3 className="text-xl font-bold text-gray-900 mb-4 heading-secondary">تحليل شامل</h3>
                                <p className="text-gray-600 text-body">
                                    تحليل السيو، الأداء، التقنيات المستخدمة، والمنافسين في تقرير واحد مفصل
                                </p>
                            </div>
                            
                            <div className="card-arabic p-8 text-center">
                                <div className="w-16 h-16 gradient-warning rounded-2xl flex items-center justify-center mx-auto mb-6">
                                    <span className="text-3xl">📄</span>
                                </div>
                                <h3 className="text-xl font-bold text-gray-900 mb-4 heading-secondary">تقارير احترافية</h3>
                                <p className="text-gray-600 text-body">
                                    تقارير PDF مفصلة باللغة العربية مع توصيات مخصصة وتصميم احترافي
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Technology Analysis Section */}
                <section className="py-20 bg-gray-50">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="text-center mb-16">
                            <h3 className="text-4xl font-bold text-gray-900 mb-6 heading-primary">
                                تحليل التقنيات المستخدمة
                            </h3>
                            <p className="text-xl text-gray-600 max-w-3xl mx-auto text-body">
                                اكتشف جميع التقنيات والبرمجيات المستخدمة في أي موقع ويب بتفاصيل دقيقة
                            </p>
                        </div>

                        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {[
                                { icon: '⚛️', title: 'Frontend', desc: 'React, Vue, Angular, jQuery' },
                                { icon: '🔧', title: 'Backend', desc: 'PHP, Node.js, Python, Java' },
                                { icon: '📝', title: 'CMS', desc: 'WordPress, Drupal, Joomla' },
                                { icon: '📈', title: 'Analytics', desc: 'Google Analytics, Facebook Pixel' },
                                { icon: '🔒', title: 'Security', desc: 'SSL, Cloudflare, Security Headers' },
                                { icon: '☁️', title: 'Hosting', desc: 'AWS, Cloudflare, GoDaddy' }
                            ].map((tech, index) => (
                                <div key={index} className="card-arabic p-6 border-arabic">
                                    <div className="flex items-center mb-4">
                                        <span className="text-3xl ml-3">{tech.icon}</span>
                                        <h4 className="text-lg font-bold text-gray-900 heading-secondary">{tech.title}</h4>
                                    </div>
                                    <p className="text-gray-600 text-body">{tech.desc}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* AI Analysis Section */}
                <section className="py-20 bg-gradient-to-r from-purple-50 to-blue-50">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="grid lg:grid-cols-2 gap-12 items-center">
                            <div className="animate-fade-in-right">
                                <h3 className="text-4xl font-bold text-gray-900 mb-6 heading-primary">
                                    تحليل بالذكاء الاصطناعي
                                </h3>
                                <p className="text-xl text-gray-600 mb-8 text-body leading-relaxed">
                                    نستخدم أحدث تقنيات الذكاء الاصطناعي لتحليل موقعك وتقديم رؤى عميقة 
                                    وتوصيات مخصصة لتحسين الأداء والسيو وتجربة المستخدم.
                                </p>
                                
                                <div className="space-y-4">
                                    {[
                                        'تحليل نقاط القوة والضعف بدقة عالية',
                                        'توصيات مخصصة حسب نوع الموقع',
                                        'تحليل المنافسين واستراتيجيات التحسين',
                                        'تقييم شامل لتجربة المستخدم'
                                    ].map((feature, index) => (
                                        <div key={index} className="flex items-center">
                                            <span className="text-green-500 text-xl ml-3">✅</span>
                                            <span className="text-gray-700 text-body">{feature}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                            
                            <div className="animate-fade-in-left">
                                <div className="card-arabic p-8 gradient-primary text-white">
                                    <h4 className="text-2xl font-bold mb-6 heading-secondary">منصات الذكاء الاصطناعي</h4>
                                    <div className="space-y-4">
                                        <div className="flex items-center">
                                            <span className="text-2xl ml-3">🧠</span>
                                            <div>
                                                <h5 className="font-bold font-cairo">OpenAI GPT</h5>
                                                <p className="text-blue-100 text-sm font-almarai">تحليل المحتوى والسيو</p>
                                            </div>
                                        </div>
                                        <div className="flex items-center">
                                            <span className="text-2xl ml-3">🤖</span>
                                            <div>
                                                <h5 className="font-bold font-cairo">Anthropic Claude</h5>
                                                <p className="text-blue-100 text-sm font-almarai">تحليل تجربة المستخدم</p>
                                            </div>
                                        </div>
                                        <div className="flex items-center">
                                            <span className="text-2xl ml-3">🔮</span>
                                            <div>
                                                <h5 className="font-bold font-cairo">Google Gemini</h5>
                                                <p className="text-blue-100 text-sm font-almarai">تحليل الأداء والتقنيات</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="bg-gray-900 text-white py-12">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="grid md:grid-cols-4 gap-8">
                            <div>
                                <div className="flex items-center mb-4">
                                    <span className="text-2xl ml-2">🔍</span>
                                    <h4 className="text-xl font-bold heading-secondary">AnalyzerDropidea</h4>
                                </div>
                                <p className="text-gray-400 text-body">
                                    محلل المواقع الاحترافي المدعوم بالذكاء الاصطناعي
                                </p>
                            </div>
                            
                            <div>
                                <h5 className="font-bold mb-4 heading-secondary">الخدمات</h5>
                                <ul className="space-y-2 text-gray-400 font-almarai">
                                    <li>تحليل السيو</li>
                                    <li>تحليل الأداء</li>
                                    <li>تحليل المنافسين</li>
                                    <li>تقارير مفصلة</li>
                                </ul>
                            </div>
                            
                            <div>
                                <h5 className="font-bold mb-4 heading-secondary">الدعم</h5>
                                <ul className="space-y-2 text-gray-400 font-almarai">
                                    <li>مركز المساعدة</li>
                                    <li>الأسئلة الشائعة</li>
                                    <li>تواصل معنا</li>
                                    <li>الدعم الفني</li>
                                </ul>
                            </div>
                            
                            <div>
                                <h5 className="font-bold mb-4 heading-secondary">تابعنا</h5>
                                <div className="flex space-x-reverse space-x-4">
                                    <a href="#" className="text-gray-400 hover:text-white transition-colors">
                                        <span className="text-xl">📘</span>
                                    </a>
                                    <a href="#" className="text-gray-400 hover:text-white transition-colors">
                                        <span className="text-xl">🐦</span>
                                    </a>
                                    <a href="#" className="text-gray-400 hover:text-white transition-colors">
                                        <span className="text-xl">📷</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div className="border-t border-gray-800 mt-8 pt-8 text-center">
                            <p className="text-gray-400 font-almarai">
                                © 2025 AnalyzerDropidea. جميع الحقوق محفوظة.
                            </p>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}

