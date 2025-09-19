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
use App\Services\UnifiedReportService;
use App\Services\ComprehensiveAnalysisService;
use Barryvdh\DomPDF\Facade\Pdf;

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

            // تحليل شامل وحقيقي للموقع (يجب أن يتم قبل AI)
            $comprehensiveService = app(ComprehensiveAnalysisService::class);
            $comprehensiveAnalysis = $comprehensiveService->performComprehensiveAnalysis($request->url);
            $analysisData['comprehensive_analysis'] = $comprehensiveAnalysis;
            
            // تحليل أمان الموقع باستخدام SecurityAnalysisService (يجب أن يتم قبل AI)
            $securityService = app(\App\Services\SecurityAnalysisService::class);
            $securityAnalysis = $securityService->analyzeWebsiteSecurity($request->url);
            $analysisData['security_score'] = $this->calculateSecurityScore($securityAnalysis);
            $analysisData['security_analysis'] = $securityAnalysis;
            
            // حساب تجربة المستخدم بناءً على مقاييس حقيقية
            $analysisData['ux_score'] = $this->calculateUXScore($analysisData, $comprehensiveAnalysis);
            
            // تحليل الذكاء الاصطناعي الشامل مع دمج التحليل الفني المفصل
            try {
                $enhancedData = array_merge($basicAnalysis, [
                    'comprehensive_analysis' => $comprehensiveAnalysis,
                    'security_analysis' => $securityAnalysis
                ]);
                
                $aiAnalysis = $this->aiAnalyzer->analyzeWebsiteWithAI(
                    $request->url, 
                    $enhancedData, 
                    $request->analysis_type
                );
            } catch (\Exception $e) {
                Log::warning('AI Analysis failed, using fallback', [
                    'url' => $request->url,
                    'error' => $e->getMessage()
                ]);
                
                // استخدام تحليل افتراضي في حالة فشل الذكاء الاصطناعي
                $aiAnalysis = [
                    'summary' => 'تم إجراء التحليل التقني بنجاح، لكن تحليل الذكاء الاصطناعي غير متاح حالياً.',
                    'overall_score' => 70,
                    'strengths' => ['تم فحص الموقع بنجاح'],
                    'weaknesses' => ['تحليل الذكاء الاصطناعي غير متاح'],
                    'seo_recommendations' => ['تحسين محتوى الموقع'],
                    'performance_recommendations' => ['تحسين سرعة التحميل']
                ];
            }
            
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

            return Inertia::render('BusinessAnalyzer', [
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
     * البحث في Google Places للأعمال مع دعم التقييد بالدولة
     */
    public function searchBusiness(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:3'
        ]);

        try {
            $googlePlaces = app(GooglePlacesService::class);
            
            // استخدام الطريقة المحدثة التي تدعم الدولة والفئة
            $results = $googlePlaces->quickSearch(
                $request->query,
                $request->country,
                $request->category
            );
            
            return response()->json([
                'success' => true,
                'businesses' => $results
            ]);
            
        } catch (\Exception $e) {
            Log::error('Business search error', [
                'query' => $request->input('query'),
                'country' => $request->input('country'),
                'category' => $request->input('category'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'فشل في البحث: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحليل عمل تجاري من Google Places
     */
    public function analyzeBusiness(Request $request)
    {
        $request->validate([
            'business_name' => 'required|string|min:3',
            'business_category' => 'required|string',
            'country' => 'required|string'
        ]);

        try {
            $googlePlaces = app(GooglePlacesService::class);
            
            // البحث عن العمل التجاري باستخدام الطريقة المحدثة
            $businessResults = $googlePlaces->quickSearch(
                $request->business_name,
                $request->country,
                $request->business_category
            );
            
            if (empty($businessResults)) {
                return back()->withErrors([
                    'business_name' => 'لم يتم العثور على أي عمل تجاري بهذا الاسم'
                ]);
            }
            
            $business = $businessResults[0];
            
            // الحصول على تفاصيل إضافية إذا توفر place_id
            $businessDetails = [];
            if (!empty($business['place_id'])) {
                $detailsResponse = $googlePlaces->getPlaceDetails($business['place_id']);
                if ($detailsResponse['success']) {
                    $businessDetails = $detailsResponse['place'];
                }
            }
            
            // إنشاء تحليل للعمل التجاري
            $analysisData = [
                'business_type' => 'google_business',
                'business_name' => $business['name'],
                'business_category' => $request->business_category,
                'country' => $request->country,
                'city' => $business['address'] ?? $request->country,
                'gmb_data' => [
                    'name' => $business['name'],
                    'address' => $business['address'] ?? '',
                    'rating' => $business['rating'] ?? 0,
                    'place_id' => $business['place_id'] ?? '',
                    'types' => $business['types'] ?? [],
                    'reviews_count' => $businessDetails['userRatingCount'] ?? 0,
                    'phone' => $businessDetails['nationalPhoneNumber'] ?? '',
                    'website' => $businessDetails['websiteUri'] ?? '',
                    'verification_status' => ($businessDetails['businessStatus'] ?? '') === 'OPERATIONAL',
                    'business_hours' => !empty($businessDetails['regularOpeningHours']) ? 'متوفرة' : 'غير متوفرة',
                    'photos_count' => count($businessDetails['photos'] ?? [])
                ],
                'analysis_date' => now(),
                'overall_score' => $this->calculateBusinessScore($business, $businessDetails),
            ];
            
            // إنشاء التقرير الموحد
            $unifiedReport = app(UnifiedReportService::class);
            $unifiedReportData = $unifiedReport->generateUnifiedReport($analysisData, null);
            
            // حفظ التحليل
            $analysis = WebsiteAnalysis::create([
                'user_id' => auth()->id(),
                'url' => $business['place_id'] ?? $business['name'],
                'region' => 'saudi',
                'analysis_type' => 'business',
                'analysis_data' => json_encode($analysisData),
                'seo_score' => null,
                'performance_score' => null,
                'load_time' => null,
                'ai_score' => $analysisData['overall_score']
            ]);
            
            // إنشاء النتيجة النهائية
            $result = $this->generateBusinessAnalysisResult($analysisData, $analysis->id);
            $result['unified_report'] = $unifiedReportData;
            
            return Inertia::render('BusinessAnalyzer', [
                'analysis' => $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('Business Analysis Error', [
                'business_name' => $request->business_name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'business_name' => 'حدث خطأ أثناء تحليل العمل التجاري: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * إنشاء نتيجة تحليل العمل التجاري
     */
    private function generateBusinessAnalysisResult($analysisData, $analysisId)
    {
        return [
            'id' => $analysisId,
            'type' => 'business_analysis',
            'business_name' => $analysisData['business_name'],
            'business_category' => $analysisData['business_category'],
            'city' => $analysisData['city'],
            'gmb_data' => $analysisData['gmb_data'],
            'overall_score' => $analysisData['overall_score'],
            'analysis_date' => $analysisData['analysis_date']->format('Y-m-d H:i:s'),
            'recommendations' => $this->generateBusinessRecommendations($analysisData)
        ];
    }
    
    /**
     * حساب نتيجة العمل التجاري
     */
    private function calculateBusinessScore($business, $details)
    {
        $score = 0;
        
        // التقييم (40%)
        if (($business['rating'] ?? 0) > 0) {
            $score += ($business['rating'] / 5) * 40;
        }
        
        // عدد المراجعات (20%)
        $reviewsCount = $details['userRatingCount'] ?? 0;
        if ($reviewsCount > 0) {
            $score += min(($reviewsCount / 100) * 20, 20);
        }
        
        // وجود الموقع الإلكتروني (15%)
        if (!empty($details['websiteUri'])) {
            $score += 15;
        }
        
        // وجود رقم الهاتف (10%)
        if (!empty($details['nationalPhoneNumber'])) {
            $score += 10;
        }
        
        // ساعات العمل (10%)
        if (!empty($details['regularOpeningHours'])) {
            $score += 10;
        }
        
        // الحالة التشغيلية (5%)
        if (($details['businessStatus'] ?? '') === 'OPERATIONAL') {
            $score += 5;
        }
        
        return round($score);
    }
    
    /**
     * توليد توصيات للعمل التجاري
     */
    private function generateBusinessRecommendations($analysisData)
    {
        $recommendations = [];
        $gmb = $analysisData['gmb_data'];
        
        if (($gmb['rating'] ?? 0) < 4.0) {
            $recommendations[] = 'تحسين جودة الخدمة للحصول على تقييمات أفضل';
        }
        
        if (($gmb['reviews_count'] ?? 0) < 10) {
            $recommendations[] = 'تشجيع العملاء على كتابة مراجعات على Google';
        }
        
        if (empty($gmb['website'])) {
            $recommendations[] = 'إضافة موقع إلكتروني للعمل التجاري';
        }
        
        if (empty($gmb['phone'])) {
            $recommendations[] = 'إضافة رقم هاتف للتواصل مع العملاء';
        }
        
        if ($gmb['business_hours'] === 'غير متوفرة') {
            $recommendations[] = 'إضافة ساعات العمل في Google My Business';
        }
        
        if (!$gmb['verification_status']) {
            $recommendations[] = 'التحقق من العمل التجاري في Google My Business';
        }
        
        return $recommendations;
    }
    
    /**
     * حساب نتيجة الأمان بناءً على التحليل الحقيقي
     */
    private function calculateSecurityScore($securityAnalysis)
    {
        $score = 0;
        
        // SSL/TLS (30 نقطة)
        if (isset($securityAnalysis['ssl_analysis']['has_ssl']) && $securityAnalysis['ssl_analysis']['has_ssl']) {
            $score += 30;
        }
        
        // Security Headers (40 نقطة)
        $headers = $securityAnalysis['security_headers'] ?? [];
        $importantHeaders = ['x-frame-options', 'x-content-type-options', 'x-xss-protection', 'strict-transport-security'];
        $headerScore = 0;
        foreach ($importantHeaders as $header) {
            if (isset($headers[$header]) && $headers[$header]['present']) {
                $headerScore += 10;
            }
        }
        $score += min($headerScore, 40);
        
        // فحص الثغرات (30 نقطة)
        if (isset($securityAnalysis['vulnerability_scan']['critical_issues'])) {
            $criticalIssues = count($securityAnalysis['vulnerability_scan']['critical_issues']);
            $score += max(0, 30 - ($criticalIssues * 10));
        } else {
            $score += 20; // نتيجة افتراضية إذا لم يتم الفحص
        }
        
        return min(100, max(0, $score));
    }
    
    /**
     * حساب نتيجة تجربة المستخدم بناءً على مقاييس حقيقية
     */
    private function calculateUXScore($analysisData, $comprehensiveAnalysis = null)
    {
        $score = 0;
        
        // سرعة التحميل (40 نقطة)
        $loadTime = $analysisData['load_time'] ?? 5;
        if ($loadTime <= 2) {
            $score += 40;
        } elseif ($loadTime <= 4) {
            $score += 30;
        } elseif ($loadTime <= 6) {
            $score += 20;
        } else {
            $score += 10;
        }
        
        // نتيجة SEO (30 نقطة)
        $seoScore = $analysisData['seo_score'] ?? 50;
        $score += ($seoScore / 100) * 30;
        
        // نتيجة الأداء (30 نقطة)
        $performanceScore = $analysisData['performance_score'] ?? 50;
        $score += ($performanceScore / 100) * 30;
        
        // مكافآت إضافية من التحليل الشامل
        if ($comprehensiveAnalysis && !isset($comprehensiveAnalysis['error'])) {
            $structure = $comprehensiveAnalysis['website_structure'] ?? [];
            
            // مكافأة للبنية الجيدة (10 نقاط إضافية)
            if (($structure['has_navigation'] ?? false) && 
                ($structure['has_footer'] ?? false) && 
                ($structure['has_header'] ?? false)) {
                $score += 5;
            }
            
            // مكافأة للنصوص البديلة (5 نقاط)
            if (($structure['missing_alt_texts'] ?? 0) === 0 && ($structure['images_count'] ?? 0) > 0) {
                $score += 5;
            }
        }
        
        return min(100, max(0, round($score)));
    }
    
    /**
     * تصدير التقرير كـ PDF
     */
    public function downloadPDF($id)
    {
        try {
            $analysis = WebsiteAnalysis::findOrFail($id);
            
            // التأكد أن التحليل خاص بالمستخدم الحالي
            if ($analysis->user_id !== auth()->id()) {
                abort(403, 'غير مصرح لك بالوصول لهذا التقرير');
            }
            
            $analysisData = json_decode($analysis->analysis_data, true);
            
            // إنشاء بيانات PDF
            $pdfData = [
                'analysis' => $analysis,
                'data' => $analysisData,
                'generated_at' => now()->format('Y/m/d H:i'),
                'is_business' => $analysis->analysis_type === 'business'
            ];
            
            // إنشاء PDF مع دعم العربية وخط DejaVu Sans المحسن
            $pdf = Pdf::loadView('reports.analysis-pdf', $pdfData)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isPhpEnabled' => true,
                    'defaultFont' => 'DejaVu Sans',
                    'isRemoteEnabled' => false,
                    'debugKeepTemp' => false,
                    'debugCss' => false,
                    'fontHeightRatio' => 1.1
                ]);
                
            $filename = 'تقرير_' . ($analysisData['business_name'] ?? 'موقع') . '_' . now()->format('Y_m_d') . '.pdf';
            
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            Log::error('PDF Export Error', [
                'analysis_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return back()->withErrors([
                'pdf' => 'فشل في تصدير التقرير: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * الحصول على مصطلح البحث للفئة
     */
    private function getCategorySearchTerm($category)
    {
        $terms = [
            'restaurant' => 'مطعم',
            'beauty_salon' => 'صالون تجميل',
            'lawyer' => 'محامي مكتب قانوني',
            'hospital' => 'مستشفى عيادة طبية',
            'school' => 'مدرسة معهد',
            'gym' => 'نادي رياضي جيم',
            'shopping_mall' => 'مول مركز تسوق',
            'car_repair' => 'ورشة سيارات',
            'real_estate_agency' => 'عقارات مكتب عقاري',
            'accounting' => 'محاسب مكتب محاسبة',
            'pharmacy' => 'صيدلية',
            'gas_station' => 'محطة وقود'
        ];
        
        return $terms[$category] ?? '';
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
