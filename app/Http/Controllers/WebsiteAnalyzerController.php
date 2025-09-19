<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use App\Services\WebsiteAnalyzerService;
use App\Services\SeoAnalyzerService;
use App\Services\PerformanceAnalyzerService;
use App\Services\CompetitorAnalyzerService;
use App\Services\ReportGeneratorService;
use App\Services\AIAnalysisService;
use App\Services\AdvancedWebsiteAnalyzerService;
use App\Services\GooglePlacesService;
use App\Services\PageSpeedService;
use App\Services\WappalyzerService;
use App\Services\SecurityAnalysisService;
use App\Models\WebsiteAnalysis;
use App\Models\WebsiteAnalysisAdvanced;
use App\Models\User;
use App\Services\GooglePlacesService;
use App\Services\UnifiedReportService;

class WebsiteAnalyzerController extends Controller
{
    protected $websiteAnalyzer;
    protected $seoAnalyzer;
    protected $performanceAnalyzer;
    protected $competitorAnalyzer;
    protected $reportGenerator;
    protected $aiAnalyzer;

    public function __construct(
        WebsiteAnalyzerService $websiteAnalyzer,
        SeoAnalyzerService $seoAnalyzer,
        PerformanceAnalyzerService $performanceAnalyzer,
        CompetitorAnalyzerService $competitorAnalyzer,
        ReportGeneratorService $reportGenerator,
        AIAnalysisService $aiAnalyzer
    ) {
        $this->websiteAnalyzer = $websiteAnalyzer;
        $this->seoAnalyzer = $seoAnalyzer;
        $this->performanceAnalyzer = $performanceAnalyzer;
        $this->competitorAnalyzer = $competitorAnalyzer;
        $this->reportGenerator = $reportGenerator;
        $this->aiAnalyzer = $aiAnalyzer;
    }

    /**
     * عرض صفحة محلل المواقع
     */
    public function index()
    {
        return Inertia::render('WebsiteAnalyzer');
    }

    /**
     * تحليل موقع ويب متقدم وشامل
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'region' => 'required|string',
            'analysis_type' => 'required|in:full,seo,performance,competitors',
            'business_name' => 'nullable|string'
        ]);

        try {
            // تحليل أساسي للموقع
            $basicAnalysis = $this->websiteAnalyzer->analyzeWebsite($request->url);
            
            // تحليل التقنيات المستخدمة بالتفصيل (مدمج في التحليل الأساسي)
            $technologies = $basicAnalysis['technologies'] ?? [];
            
            $analysisData = [
                'url' => $request->url,
                'region' => $request->region,
                'analysis_type' => $request->analysis_type,
                'business_name' => $request->business_name,
                'basic_info' => $basicAnalysis,
                'technologies' => $technologies,
                'analyzed_at' => now(),
            ];

            // تحليل السيو إذا كان مطلوباً
            if (in_array($request->analysis_type, ['full', 'seo'])) {
                $seoAnalysis = $this->seoAnalyzer->analyzeSeo($request->url);
                $analysisData['seo_analysis'] = $seoAnalysis;
                $analysisData['seo_score'] = $seoAnalysis['score'] ?? 0;
            }

            // تحليل الأداء إذا كان مطلوباً
            if (in_array($request->analysis_type, ['full', 'performance'])) {
                $performanceAnalysis = $this->performanceAnalyzer->analyzePerformance($request->url);
                $analysisData['performance_analysis'] = $performanceAnalysis;
                $analysisData['performance_score'] = $performanceAnalysis['score'] ?? 0;
                $analysisData['load_time'] = $performanceAnalysis['load_time'] ?? 0;
            }

            // تحليل المنافسين إذا كان مطلوباً
            if (in_array($request->analysis_type, ['full', 'competitors'])) {
                $competitorAnalysis = $this->competitorAnalyzer->analyzeCompetitors($request->url, $request->region);
                $analysisData['competitor_analysis'] = $competitorAnalysis;
                $analysisData['competitors_count'] = count($competitorAnalysis['competitors'] ?? []);
            }

            // تحليل الذكاء الاصطناعي الشامل
            $aiAnalysis = $this->aiAnalyzer->analyzeWebsiteWithAI(
                $request->url, 
                $basicAnalysis, 
                $request->analysis_type
            );
            
            // إصلاح بنية بيانات AI لتكون متوافقة مع الواجهة الأمامية
            if (isset($aiAnalysis['analysis']) && !isset($aiAnalysis['summary'])) {
                $aiAnalysis['summary'] = $aiAnalysis['analysis'];
            }
            
            // تأكد من وجود overall_score
            if (!isset($aiAnalysis['overall_score']) && isset($aiAnalysis['score'])) {
                $aiAnalysis['overall_score'] = $aiAnalysis['score'];
            }
            
            // إضافة بيانات افتراضية إذا كانت مفقودة
            if (!isset($aiAnalysis['strengths'])) {
                $aiAnalysis['strengths'] = [];
            }
            if (!isset($aiAnalysis['weaknesses'])) {
                $aiAnalysis['weaknesses'] = [];
            }
            if (!isset($aiAnalysis['seo_recommendations'])) {
                $aiAnalysis['seo_recommendations'] = [];
            }
            if (!isset($aiAnalysis['performance_recommendations'])) {
                $aiAnalysis['performance_recommendations'] = [];
            }
            
            $analysisData['ai_analysis'] = $aiAnalysis;

            // تحسين البيانات مع Google Places إذا تم تحديد اسم العمل
            if (!empty($request->business_name)) {
                $googlePlaces = app(GooglePlacesService::class);
                $gmbResults = $googlePlaces->quickSearch($request->business_name);
                
                if (!empty($gmbResults)) {
                    $analysisData['gmb_data'] = [
                        'name' => $gmbResults[0]['name'] ?? '',
                        'address' => $gmbResults[0]['address'] ?? '',
                        'rating' => $gmbResults[0]['rating'] ?? 0,
                        'place_id' => $gmbResults[0]['place_id'] ?? ''
                    ];
                }
            }
            
            // إضافة النتائج المتقدمة (تحليل أمان، UX، إلخ)
            $analysisData['security_score'] = 75; // نتيجة افتراضية للأمان
            $analysisData['ux_score'] = 70; // نتيجة افتراضية لتجربة المستخدم
            $analysisData['overall_score'] = $this->calculateOverallScore($analysisData);

            // حفظ التحليل في قاعدة البيانات
            $analysis = WebsiteAnalysis::create([
                'user_id' => auth()->id(),
                'url' => $request->url,
                'region' => $request->region,
                'analysis_type' => $request->analysis_type,
                'analysis_data' => json_encode($analysisData),
                'seo_score' => $analysisData['seo_score'] ?? null,
                'performance_score' => $analysisData['performance_score'] ?? null,
                'load_time' => $analysisData['load_time'] ?? null,
                'ai_score' => $aiAnalysis['overall_score'] ?? $aiAnalysis['score'] ?? null,
            ]);

            // إنشاء التقرير الموحد للمرحلة الثانية
            $unifiedReport = app(UnifiedReportService::class);
            $report = $unifiedReport->generateUnifiedReport($analysisData, $analysis->id);
            
            // إنشاء ملخص النتائج للعرض مع التقرير الموحد
            $result = $this->generateEnhancedAnalysisResult($analysisData, $analysis->id);
            $result['unified_report'] = $report;

            return Inertia::render('WebsiteAnalyzer', [
                'analysis' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Advanced Analysis Error', [
                'url' => $request->url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'url' => 'حدث خطأ أثناء تحليل الموقع: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * البحث في Google Places للأعمال
     */
    public function searchBusiness(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:3'
        ]);

        try {
            $googlePlaces = app(GooglePlacesService::class);
            $results = $googlePlaces->quickSearch($request->query);
            
            return response()->json([
                'success' => true,
                'businesses' => $results
            ]);
            
        } catch (\Exception $e) {
            Log::error('Business search error', [
                'query' => $request->query,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'فشل في البحث: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * حساب النتيجة الإجمالية
     */
    private function calculateOverallScore($analysisData)
    {
        $scores = [];
        
        if (!empty($analysisData['seo_score'])) {
            $scores[] = $analysisData['seo_score'];
        }
        
        if (!empty($analysisData['performance_score'])) {
            $scores[] = $analysisData['performance_score'];
        }
        
        if (!empty($analysisData['security_score'])) {
            $scores[] = $analysisData['security_score'];
        }
        
        if (!empty($analysisData['ux_score'])) {
            $scores[] = $analysisData['ux_score'];
        }
        
        if (!empty($analysisData['ai_analysis']['overall_score'])) {
            $scores[] = $analysisData['ai_analysis']['overall_score'];
        }
        
        return !empty($scores) ? round(array_sum($scores) / count($scores)) : 70;
    }


    /**
     * إنشاء ملخص نتائج التحليل المحسن مع الذكاء الاصطناعي
     */
    private function generateEnhancedAnalysisResult($analysisData, $analysisId)
    {
        $result = [
            'id' => $analysisId,
            'url' => $analysisData['url'] ?? '',
            'overall_score' => $analysisData['overall_score'] ?? 0,
            'seo_score' => $analysisData['seo_score'] ?? $analysisData['seo_analysis']['score'] ?? 0,
            'performance_score' => $analysisData['performance_score'] ?? $analysisData['performance_analysis']['score'] ?? 0,
            'security_score' => $analysisData['security_score'] ?? 75,
            'ux_score' => $analysisData['ux_score'] ?? 70,
            'ai_score' => $analysisData['ai_analysis']['overall_score'] ?? $analysisData['ai_analysis']['score'] ?? 0,
            'load_time' => $analysisData['load_time'] ?? $analysisData['performance_analysis']['load_time'] ?? 0,
            'competitors_count' => $analysisData['competitors_count'] ?? 0,
            'technologies' => $analysisData['technologies'] ?? [],
            'ai_analysis' => [],
            'strengths' => [],
            'weaknesses' => [],
            'recommendations' => [],
            'detailed_sections' => []
        ];

        // دمج نقاط القوة والضعف من الذكاء الاصطناعي
        if (isset($analysisData['ai_analysis'])) {
            $aiAnalysis = $analysisData['ai_analysis'];
            
            // تحضير ai_analysis للواجهة الأمامية
            $result['ai_analysis'] = [
                'summary' => $aiAnalysis['summary'] ?? $aiAnalysis['analysis'] ?? 'تم تحليل الموقع باستخدام الذكاء الاصطناعي',
                'overall_score' => $aiAnalysis['overall_score'] ?? $aiAnalysis['score'] ?? 0,
                'analysis' => $aiAnalysis['analysis'] ?? '',
                'strengths' => $aiAnalysis['strengths'] ?? [],
                'weaknesses' => $aiAnalysis['weaknesses'] ?? [],
                'seo_recommendations' => $aiAnalysis['seo_recommendations'] ?? [],
                'performance_recommendations' => $aiAnalysis['performance_recommendations'] ?? [],
                'security_recommendations' => $aiAnalysis['security_recommendations'] ?? [],
                'ux_recommendations' => $aiAnalysis['ux_recommendations'] ?? []
            ];
            
            $result['ai_summary'] = $result['ai_analysis']['summary'];
            
            $result['strengths'] = array_merge(
                $result['strengths'], 
                $aiAnalysis['strengths'] ?? []
            );
            
            $result['weaknesses'] = array_merge(
                $result['weaknesses'], 
                $aiAnalysis['weaknesses'] ?? []
            );

            // إضافة التوصيات المختلفة
            $result['detailed_sections'] = [
                'seo_recommendations' => $aiAnalysis['seo_recommendations'] ?? [],
                'performance_recommendations' => $aiAnalysis['performance_recommendations'] ?? [],
                'security_recommendations' => $aiAnalysis['security_recommendations'] ?? [],
                'ux_recommendations' => $aiAnalysis['ux_recommendations'] ?? [],
                'content_recommendations' => $aiAnalysis['content_recommendations'] ?? [],
                'marketing_strategies' => $aiAnalysis['marketing_strategies'] ?? [],
                'competitor_insights' => $aiAnalysis['competitor_insights'] ?? []
            ];

            // دمج جميع التوصيات
            $allRecommendations = array_merge(
                $aiAnalysis['seo_recommendations'] ?? [],
                $aiAnalysis['performance_recommendations'] ?? [],
                $aiAnalysis['security_recommendations'] ?? [],
                $aiAnalysis['ux_recommendations'] ?? [],
                $aiAnalysis['content_recommendations'] ?? []
            );
            
            $result['recommendations'] = array_slice($allRecommendations, 0, 10);
        }

        // تحليل التقنيات المستخدمة
        if (isset($analysisData['technologies'])) {
            $technologies = $analysisData['technologies'];
            $techSummary = [];
            
            foreach ($technologies as $category => $techs) {
                if (!empty($techs)) {
                    $techSummary[$category] = $techs;
                }
            }
            
            $result['technologies_summary'] = $techSummary;
        }

        // إضافة بيانات SEO للواجهة الأمامية
        if (isset($analysisData['seo_analysis'])) {
            $seoAnalysis = $analysisData['seo_analysis'];
            
            $result['seo_analysis'] = $seoAnalysis;
            
            if ($seoAnalysis['score'] >= 80) {
                $result['strengths'][] = 'تحسين محركات البحث ممتاز (' . $seoAnalysis['score'] . '/100)';
            } elseif ($seoAnalysis['score'] < 50) {
                $result['weaknesses'][] = 'تحسين محركات البحث يحتاج إلى تطوير (' . $seoAnalysis['score'] . '/100)';
                $result['recommendations'][] = 'تحسين العناوين والوصف التعريفي والكلمات المفتاحية';
            }

            if ($seoAnalysis['has_meta_description']) {
                $result['strengths'][] = 'يحتوي على وصف تعريفي مناسب';
            } else {
                $result['weaknesses'][] = 'لا يحتوي على وصف تعريفي';
                $result['recommendations'][] = 'إضافة وصف تعريفي جذاب لجميع الصفحات';
            }
        }

        // إضافة بيانات الأداء للواجهة الأمامية
        if (isset($analysisData['performance_analysis'])) {
            $perfAnalysis = $analysisData['performance_analysis'];
            
            $result['performance_analysis'] = $perfAnalysis;
            
            if ($perfAnalysis['load_time'] <= 2.0) {
                $result['strengths'][] = 'سرعة تحميل ممتازة (' . $perfAnalysis['load_time'] . ' ثانية)';
            } elseif ($perfAnalysis['load_time'] > 5.0) {
                $result['weaknesses'][] = 'سرعة التحميل بطيئة (' . $perfAnalysis['load_time'] . ' ثانية)';
                $result['recommendations'][] = 'تحسين كود الموقع وتحسين استخدام الذاكرة';
            }
        }

        // تحليل الأداء التقليدي
        if (isset($analysisData['performance_analysis'])) {
            $performanceAnalysis = $analysisData['performance_analysis'];
            
            if ($performanceAnalysis['load_time'] < 3) {
                $result['strengths'][] = 'سرعة تحميل ممتازة (' . $performanceAnalysis['load_time'] . ' ثانية)';
            } elseif ($performanceAnalysis['load_time'] > 5) {
                $result['weaknesses'][] = 'سرعة التحميل بطيئة (' . $performanceAnalysis['load_time'] . ' ثانية)';
                $result['recommendations'][] = 'تحسين سرعة التحميل وضغط الصور وتقليل حجم الملفات';
            }

            if ($performanceAnalysis['score'] >= 80) {
                $result['strengths'][] = 'أداء الموقع ممتاز (' . $performanceAnalysis['score'] . '/100)';
            } elseif ($performanceAnalysis['score'] < 50) {
                $result['weaknesses'][] = 'أداء الموقع يحتاج إلى تحسين (' . $performanceAnalysis['score'] . '/100)';
                $result['recommendations'][] = 'تحسين كود الموقع وتحسين استخدام الذاكرة';
            }
        }

        // إزالة التكرارات
        $result['strengths'] = array_unique($result['strengths']);
        $result['weaknesses'] = array_unique($result['weaknesses']);
        $result['recommendations'] = array_unique($result['recommendations']);

        return $result;
    }

    /**
     * تحميل تقرير PDF محسن
     */
    public function downloadPDF($id)
    {
        $analysis = WebsiteAnalysis::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $analysisData = json_decode($analysis->analysis_data, true);
        
        // إنشاء تقرير PDF محسن مع تحليل الذكاء الاصطناعي
        $pdfContent = $this->reportGenerator->generateEnhancedPDFReport($analysisData);
        
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="ai-website-analysis-report-' . $id . '.pdf"');
    }

    /**
     * عرض تاريخ التحليلات
     */
    public function history()
    {
        $analyses = WebsiteAnalysis::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('AnalysisHistory', [
            'analyses' => $analyses
        ]);
    }

    /**
     * عرض تحليل محدد
     */
    public function show($id)
    {
        $analysis = WebsiteAnalysis::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $analysisData = json_decode($analysis->analysis_data, true);
        $result = $this->generateEnhancedAnalysisResult($analysisData, $analysis->id);

        return Inertia::render('WebsiteAnalyzer', [
            'analysis' => $result
        ]);
    }
}
