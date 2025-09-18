<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Services\AdvancedWebsiteAnalyzerService;
use App\Models\WebsiteAnalysisAdvanced;
use App\Models\GmbEntity;
use Inertia\Inertia;

class AdvancedWebsiteAnalyzerController extends Controller
{
    protected $advancedAnalyzer;
    
    public function __construct(AdvancedWebsiteAnalyzerService $advancedAnalyzer)
    {
        $this->advancedAnalyzer = $advancedAnalyzer;
    }
    
    /**
     * عرض صفحة التحليل المتقدم
     */
    public function index()
    {
        $recentAnalyses = WebsiteAnalysisAdvanced::where('user_id', Auth::id())
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($analysis) {
                return [
                    'id' => $analysis->id,
                    'domain' => $analysis->domain,
                    'composite_score' => $analysis->composite_score,
                    'status' => $analysis->status,
                    'created_at' => $analysis->created_at->format('Y-m-d H:i'),
                    'analysis_time' => $analysis->total_analysis_time ? $analysis->total_analysis_time . ' ثانية' : null
                ];
            });
        
        return Inertia::render('AnalyzerDropidea', [
            'recent_analyses' => $recentAnalyses,
            'user' => Auth::user()
        ]);
    }
    
    /**
     * التحليل الشامل للموقع - النسخة المتقدمة
     */
    public function analyze(Request $request)
    {
        // التحقق من صحة البيانات
        $validator = Validator::make($request->all(), [
            'url' => 'required|url|max:2048',
            'business_name' => 'nullable|string|max:255',
            'analysis_type' => 'in:website,business'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors()
            ], 400);
        }

        $url = $request->input('url');
        $businessName = $request->input('business_name');
        $analysisType = $request->input('analysis_type', 'website');
        
        try {
            // تنظيف الرابط
            $cleanUrl = $this->cleanUrl($url);
            
            // التحليل الشامل
            $result = $this->advancedAnalyzer->analyzeWebsiteComprehensive(
                $cleanUrl, 
                Auth::id(),
                $businessName
            );
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'فشل في التحليل'
                ], 500);
            }
            
            // تنسيق النتائج للواجهة الأمامية
            $formattedData = $this->formatAnalysisForFrontend($result['data']);
            
            return response()->json([
                'success' => true,
                'message' => 'تم التحليل بنجاح! 🎉',
                'analysis_id' => $result['analysis_id'],
                'data' => $formattedData
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحليل: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * البحث عن الأعمال باستخدام Google Places
     */
    public function searchBusiness(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_name' => 'required|string|max:255',
            'location' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }
        
        try {
            $googlePlacesService = app(\App\Services\GooglePlacesService::class);
            $result = $googlePlacesService->searchByBusinessName(
                $request->business_name,
                $request->location
            );
            
            if ($result['success']) {
                $businesses = collect($result['places'])->map(function ($place) {
                    return [
                        'place_id' => $place['id'],
                        'name' => $place['displayName']['text'] ?? '',
                        'address' => $place['formattedAddress'] ?? '',
                        'website' => $place['websiteUri'] ?? null,
                        'rating' => $place['rating'] ?? null,
                        'reviews_count' => $place['userRatingCount'] ?? 0
                    ];
                });
                
                return response()->json([
                    'success' => true,
                    'businesses' => $businesses
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على أعمال'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البحث: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * عرض نتائج التحليل المحفوظ
     */
    public function showAnalysis($analysisId)
    {
        $analysis = WebsiteAnalysisAdvanced::where('id', $analysisId)
            ->where('user_id', Auth::id())
            ->with(['auditRuns'])
            ->firstOrFail();
            
        $analysisData = $this->formatSavedAnalysisForFrontend($analysis);
        
        return Inertia::render('AdvancedAnalysisResults', [
            'analysis' => $analysisData,
            'analysis_id' => $analysisId
        ]);
    }
    
    // Helper Methods
    
    /**
     * تنظيف الرابط
     */
    protected function cleanUrl($url)
    {
        // إضافة https إذا لم يكن موجود
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }
        
        // إزالة المسافات والعلامات غير الضرورية
        $url = trim($url);
        
        return $url;
    }
    
    /**
     * تنسيق نتائج التحليل للواجهة الأمامية
     */
    protected function formatAnalysisForFrontend($data)
    {
        return [
            'basic_info' => $data['basic_info'],
            'scores' => [
                'overall' => $data['composite_scores']['overall'] ?? 0,
                'performance' => $data['composite_scores']['performance'] ?? 0,
                'security' => $data['composite_scores']['security'] ?? 0,
                'seo' => $data['composite_scores']['seo'] ?? 0,
                'ux' => $data['composite_scores']['ux'] ?? 0,
                'maps_presence' => $data['composite_scores']['maps_presence'] ?? 0
            ],
            'performance' => [
                'mobile_score' => $data['performance']['mobile_score'] ?? null,
                'desktop_score' => $data['performance']['desktop_score'] ?? null,
                'core_web_vitals' => $data['performance']['core_web_vitals'] ?? [],
                'lighthouse_scores' => $data['performance']['lighthouse_scores'] ?? []
            ],
            'security' => [
                'ssl_analysis' => $data['security']['ssl_analysis'] ?? [],
                'security_headers' => $data['security']['security_headers'] ?? [],
                'security_score' => $data['composite_scores']['security'] ?? 0
            ],
            'technologies' => $data['technologies'] ?? [],
            'seo' => [
                'metadata' => $data['metadata'] ?? [],
                'score' => $data['composite_scores']['seo'] ?? 0
            ],
            'google_business' => $data['google_business'] ?? null,
            'ai_insights' => $data['ai_insights'] ?? '',
            'recommendations' => $data['recommendations'] ?? []
        ];
    }
    
    /**
     * تنسيق التحليل المحفوظ للواجهة الأمامية
     */
    protected function formatSavedAnalysisForFrontend($analysis)
    {
        return [
            'id' => $analysis->id,
            'url' => $analysis->url,
            'domain' => $analysis->domain,
            'status' => $analysis->status,
            'created_at' => $analysis->created_at->format('Y-m-d H:i:s'),
            'analysis_time' => $analysis->total_analysis_time,
            'scores' => [
                'overall' => $analysis->composite_score,
                'performance' => $analysis->performance_score,
                'security' => $analysis->security_score,
                'seo' => $analysis->seo_score,
                'ux' => $analysis->ux_score,
                'maps_presence' => $analysis->maps_presence_score
            ],
            'performance_data' => [
                'mobile_score' => $analysis->pagespeed_mobile,
                'desktop_score' => $analysis->pagespeed_desktop,
                'core_web_vitals' => $analysis->core_web_vitals,
                'lighthouse_performance' => $analysis->lighthouse_performance,
                'lighthouse_seo' => $analysis->lighthouse_seo,
                'lighthouse_accessibility' => $analysis->lighthouse_accessibility,
                'lighthouse_best_practices' => $analysis->lighthouse_best_practices
            ],
            'security_data' => [
                'has_ssl' => $analysis->has_ssl,
                'ssl_grade' => $analysis->ssl_grade,
                'security_headers' => $analysis->security_headers,
                'security_issues' => $analysis->security_issues
            ],
            'technology_data' => [
                'stack_detection' => $analysis->stack_detection,
                'technologies' => $analysis->technologies
            ],
            'seo_data' => [
                'metadata' => $analysis->metadata,
                'open_graph' => $analysis->open_graph,
                'twitter_cards' => $analysis->twitter_cards,
                'schema_org' => $analysis->schema_org,
                'has_robots_txt' => $analysis->has_robots_txt,
                'has_sitemap' => $analysis->has_sitemap
            ]
        ];
    }
}
