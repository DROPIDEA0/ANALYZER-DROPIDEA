<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\WebsiteAnalyzerService;
use App\Services\SeoAnalyzerService;
use App\Services\PerformanceAnalyzerService;
use App\Services\CompetitorAnalyzerService;
use App\Services\ReportGeneratorService;
use App\Services\AIAnalysisService;
use App\Models\WebsiteAnalysis;
use App\Models\User;

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
     * تحليل موقع ويب مع الذكاء الاصطناعي
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'region' => 'required|string',
            'analysis_type' => 'required|in:full,seo,performance,competitors'
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
                'basic_info' => $basicAnalysis,
                'technologies' => $technologies,
                'analyzed_at' => now(),
            ];

            // تحليل السيو إذا كان مطلوباً
            if (in_array($request->analysis_type, ['full', 'seo'])) {
                $seoAnalysis = $this->seoAnalyzer->analyzeSeo($request->url);
                $analysisData['seo_analysis'] = $seoAnalysis;
                $analysisData['seo_score'] = $seoAnalysis['score'];
            }

            // تحليل الأداء إذا كان مطلوباً
            if (in_array($request->analysis_type, ['full', 'performance'])) {
                $performanceAnalysis = $this->performanceAnalyzer->analyzePerformance($request->url);
                $analysisData['performance_analysis'] = $performanceAnalysis;
                $analysisData['performance_score'] = $performanceAnalysis['score'];
                $analysisData['load_time'] = $performanceAnalysis['load_time'];
            }

            // تحليل المنافسين إذا كان مطلوباً
            if (in_array($request->analysis_type, ['full', 'competitors'])) {
                $competitorAnalysis = $this->competitorAnalyzer->analyzeCompetitors($request->url, $request->region);
                $analysisData['competitor_analysis'] = $competitorAnalysis;
                $analysisData['competitors_count'] = count($competitorAnalysis['competitors']);
            }

            // تحليل الذكاء الاصطناعي الشامل
            $aiAnalysis = $this->aiAnalyzer->analyzeWebsiteWithAI(
                $request->url, 
                $basicAnalysis, 
                $request->analysis_type
            );
            $analysisData['ai_analysis'] = $aiAnalysis;

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
                'ai_score' => $aiAnalysis['overall_score'] ?? null,
            ]);

            // إنشاء ملخص النتائج للعرض
            $result = $this->generateEnhancedAnalysisResult($analysisData, $analysis->id);

            return Inertia::render('WebsiteAnalyzer', [
                'analysis' => $result
            ]);

        } catch (\Exception $e) {
            return back()->withErrors([
                'url' => 'حدث خطأ أثناء تحليل الموقع: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * إنشاء ملخص نتائج التحليل المحسن مع الذكاء الاصطناعي
     */
    private function generateEnhancedAnalysisResult($analysisData, $analysisId)
    {
        $result = [
            'id' => $analysisId,
            'url' => $analysisData['url'],
            'seo_score' => $analysisData['seo_score'] ?? 0,
            'performance_score' => $analysisData['performance_score'] ?? 0,
            'ai_score' => $analysisData['ai_analysis']['overall_score'] ?? 0,
            'load_time' => $analysisData['load_time'] ?? 0,
            'competitors_count' => $analysisData['competitors_count'] ?? 0,
            'technologies' => $analysisData['technologies'] ?? [],
            'ai_analysis' => $analysisData['ai_analysis'] ?? [],
            'strengths' => [],
            'weaknesses' => [],
            'recommendations' => [],
            'detailed_sections' => []
        ];

        // دمج نقاط القوة والضعف من الذكاء الاصطناعي
        if (isset($analysisData['ai_analysis'])) {
            $aiAnalysis = $analysisData['ai_analysis'];
            $result['ai_summary'] = $aiAnalysis['analysis'] ?? $aiAnalysis['summary'] ?? '';
            
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

        // تحليل السيو التقليدي
        if (isset($analysisData['seo_analysis'])) {
            $seoAnalysis = $analysisData['seo_analysis'];
            
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
