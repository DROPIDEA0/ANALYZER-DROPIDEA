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

class WebsiteAnalyzerController extends Controller
{
    protected $websiteAnalyzer;
    protected $seoAnalyzer;
    protected $performanceAnalyzer;
    protected $competitorAnalyzer;
    protected $reportGenerator;
    protected $aiAnalyzer;
    protected $advancedAnalyzer;
    protected $googlePlaces;
    protected $pageSpeed;
    protected $wappalyzer;
    protected $securityAnalysis;

    public function __construct(
        WebsiteAnalyzerService $websiteAnalyzer,
        SeoAnalyzerService $seoAnalyzer,
        PerformanceAnalyzerService $performanceAnalyzer,
        CompetitorAnalyzerService $competitorAnalyzer,
        ReportGeneratorService $reportGenerator,
        AIAnalysisService $aiAnalyzer,
        AdvancedWebsiteAnalyzerService $advancedAnalyzer,
        GooglePlacesService $googlePlaces,
        PageSpeedService $pageSpeed,
        WappalyzerService $wappalyzer,
        SecurityAnalysisService $securityAnalysis
    ) {
        $this->websiteAnalyzer = $websiteAnalyzer;
        $this->seoAnalyzer = $seoAnalyzer;
        $this->performanceAnalyzer = $performanceAnalyzer;
        $this->competitorAnalyzer = $competitorAnalyzer;
        $this->reportGenerator = $reportGenerator;
        $this->aiAnalyzer = $aiAnalyzer;
        $this->advancedAnalyzer = $advancedAnalyzer;
        $this->googlePlaces = $googlePlaces;
        $this->pageSpeed = $pageSpeed;
        $this->wappalyzer = $wappalyzer;
        $this->securityAnalysis = $securityAnalysis;
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
            // استخدام النظام المتقدم للتحليل الشامل
            $analysisResult = $this->advancedAnalyzer->analyzeWebsite(
                $request->url,
                $request->business_name
            );

            // حفظ التحليل المتقدم في الجدول الجديد
            $analysis = WebsiteAnalysisAdvanced::create([
                'user_id' => auth()->id(),
                'url' => $request->url,
                'business_name' => $request->business_name,
                'overall_score' => $analysisResult['overall_score'],
                'performance_score' => $analysisResult['performance_score'],
                'seo_score' => $analysisResult['seo_score'],
                'security_score' => $analysisResult['security_score'],
                'ux_score' => $analysisResult['ux_score'],
                'ai_score' => $analysisResult['ai_score'],
                'technology_score' => $analysisResult['technology_score'],
                'performance_data' => json_encode($analysisResult['performance_analysis'] ?? []),
                'seo_data' => json_encode($analysisResult['seo_analysis'] ?? []),
                'security_data' => json_encode($analysisResult['security_analysis'] ?? []),
                'technology_data' => json_encode($analysisResult['technology_analysis'] ?? []),
                'ai_insights' => $analysisResult['ai_analysis'] ?? '',
                'recommendations' => json_encode($analysisResult['recommendations'] ?? []),
                'gmb_data' => json_encode($analysisResult['gmb_data'] ?? []),
                'load_time' => $analysisResult['load_time'] ?? null,
                'page_size' => $analysisResult['page_size'] ?? null,
                'analysis_metadata' => json_encode([
                    'region' => $request->region,
                    'analysis_type' => $request->analysis_type,
                    'analyzed_at' => now(),
                    'user_agent' => $request->userAgent(),
                    'ip_address' => $request->ip()
                ])
            ]);

            // تحضير النتيجة للعرض في الواجهة
            $displayResult = $this->prepareAdvancedResultForDisplay($analysisResult, $analysis->id);

            return Inertia::render('WebsiteAnalyzer', [
                'analysis' => $displayResult
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
            'query' => 'required|string'
        ]);

        try {
            $results = $this->googlePlaces->searchBusiness($request->query);
            
            return response()->json([
                'success' => true,
                'businesses' => $results
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في البحث: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * تحضير النتيجة المتقدمة للعرض
     */
    private function prepareAdvancedResultForDisplay($analysisResult, $analysisId)
    {
        return [
            'id' => $analysisId,
            'url' => $analysisResult['url'] ?? '',
            'overall_score' => $analysisResult['overall_score'] ?? 0,
            'seo_score' => $analysisResult['seo_score'] ?? 0,
            'performance_score' => $analysisResult['performance_score'] ?? 0,
            'security_score' => $analysisResult['security_score'] ?? 0,
            'ux_score' => $analysisResult['ux_score'] ?? 0,
            'ai_score' => $analysisResult['ai_score'] ?? 0,
            'technology_score' => $analysisResult['technology_score'] ?? 0,
            'load_time' => $analysisResult['load_time'] ?? 0,
            'page_size' => $analysisResult['page_size'] ?? 0,
            
            // تحليلات مفصلة
            'performance_analysis' => $analysisResult['performance_analysis'] ?? [],
            'seo_analysis' => $analysisResult['seo_analysis'] ?? [],
            'security_analysis' => $analysisResult['security_analysis'] ?? [],
            'technology_analysis' => $analysisResult['technology_analysis'] ?? [],
            
            // نقاط القوة والضعف
            'strengths' => $analysisResult['strengths'] ?? [],
            'weaknesses' => $analysisResult['weaknesses'] ?? [],
            
            // التوصيات
            'recommendations' => $analysisResult['recommendations'] ?? [],
            'detailed_sections' => [
                'seo_recommendations' => $analysisResult['seo_recommendations'] ?? [],
                'performance_recommendations' => $analysisResult['performance_recommendations'] ?? [],
                'security_recommendations' => $analysisResult['security_recommendations'] ?? [],
                'ux_recommendations' => $analysisResult['ux_recommendations'] ?? [],
            ],
            
            // رؤى الذكاء الاصطناعي
            'ai_analysis' => [
                'summary' => $analysisResult['ai_analysis'] ?? 'تم إجراء تحليل شامل للموقع',
                'analysis' => $analysisResult['ai_analysis'] ?? '',
                'overall_score' => $analysisResult['ai_score'] ?? 0
            ],
            'ai_summary' => $analysisResult['ai_analysis'] ?? '',
            
            // معلومات Google My Business
            'gmb_data' => $analysisResult['gmb_data'] ?? [],
            
            // ملخص التقنيات
            'technologies_summary' => $analysisResult['technology_analysis'] ?? [],
            'technologies' => $analysisResult['technology_analysis'] ?? []
        ];
    }

    /**
     * إنشاء ملخص نتائج التحليل المحسن مع الذكاء الاصطناعي
     */
    private function generateEnhancedAnalysisResult($analysisData, $analysisId)
    {
        $result = [
            'id' => $analysisId,
            'url' => $analysisData['url'] ?? '',
            'seo_score' => $analysisData['seo_score'] ?? $analysisData['seo_analysis']['score'] ?? 0,
            'performance_score' => $analysisData['performance_score'] ?? $analysisData['performance_analysis']['score'] ?? 0,
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
