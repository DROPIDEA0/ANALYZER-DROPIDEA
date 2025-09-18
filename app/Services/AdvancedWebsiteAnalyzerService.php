<?php

namespace App\Services;

use App\Models\WebsiteAnalysisAdvanced;
use App\Models\GmbEntity;
use App\Models\Competitor;
use App\Models\AuditRun;
use App\Services\GooglePlacesService;
use App\Services\PageSpeedService;
use App\Services\WappalyzerService;
use App\Services\SecurityAnalysisService;
use App\Services\AIAnalysisService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AdvancedWebsiteAnalyzerService
{
    protected $googlePlacesService;
    protected $pageSpeedService;
    protected $wappalyzerService;
    protected $securityService;
    protected $aiService;
    
    public function __construct(
        GooglePlacesService $googlePlacesService,
        PageSpeedService $pageSpeedService,
        WappalyzerService $wappalyzerService,
        SecurityAnalysisService $securityService,
        AIAnalysisService $aiService
    ) {
        $this->googlePlacesService = $googlePlacesService;
        $this->pageSpeedService = $pageSpeedService;
        $this->wappalyzerService = $wappalyzerService;
        $this->securityService = $securityService;
        $this->aiService = $aiService;
    }
    
    /**
     * التحليل الشامل للموقع - Phase 1
     */
    public function analyzeWebsiteComprehensive($url, $userId, $businessName = null)
    {
        try {
            DB::beginTransaction();
            
            // إنشاء سجل التحليل المتقدم
            $analysis = WebsiteAnalysisAdvanced::create([
                'user_id' => $userId,
                'url' => $url,
                'domain' => parse_url($url, PHP_URL_HOST),
                'status' => 'processing',
                'analysis_started_at' => now()
            ]);
            
            Log::info('بدء التحليل الشامل', [
                'analysis_id' => $analysis->id,
                'url' => $url,
                'business_name' => $businessName
            ]);
            
            // المرحلة 1: تحليل الأداء والـ Core Web Vitals
            $performanceData = $this->analyzePerformance($analysis, $url);
            
            // المرحلة 2: كشف التقنيات
            $technologyData = $this->analyzeTechnologies($analysis, $url);
            
            // المرحلة 3: تحليل الأمان
            $securityData = $this->analyzeSecurity($analysis, $url);
            
            // المرحلة 4: تحليل الـ Metadata والـ SEO
            $metadataData = $this->analyzeMetadata($analysis, $url);
            
            // المرحلة 5: البحث عن بيانات Google My Business (إذا تم تمرير اسم العمل)
            $gmbData = null;
            if ($businessName) {
                $gmbData = $this->analyzeGoogleMyBusiness($analysis, $businessName, $url);
            }
            
            // المرحلة 6: حساب النتيجة المركبة
            $compositeScore = $this->calculateCompositeScore($analysis, $performanceData, $securityData, $metadataData, $gmbData);
            
            // المرحلة 7: تحليل الذكاء الاصطناعي
            $aiAnalysis = $this->generateAIInsights($analysis, $performanceData, $securityData, $technologyData, $metadataData);
            
            // تحديث السجل
            $analysis->update([
                'status' => 'completed',
                'analysis_completed_at' => now(),
                'total_analysis_time' => now()->diffInSeconds($analysis->analysis_started_at),
                'composite_score' => $compositeScore['overall'],
                'performance_score' => $compositeScore['performance'],
                'security_score' => $compositeScore['security'],
                'seo_score' => $compositeScore['seo'],
                'ux_score' => $compositeScore['ux'],
                'maps_presence_score' => $compositeScore['maps_presence']
            ]);
            
            DB::commit();
            
            return [
                'success' => true,
                'analysis_id' => $analysis->id,
                'data' => [
                    'basic_info' => [
                        'url' => $url,
                        'domain' => $analysis->domain,
                        'analysis_time' => $analysis->total_analysis_time . ' ثانية'
                    ],
                    'performance' => $performanceData,
                    'technologies' => $technologyData,
                    'security' => $securityData,
                    'metadata' => $metadataData,
                    'google_business' => $gmbData,
                    'composite_scores' => $compositeScore,
                    'ai_insights' => $aiAnalysis,
                    'recommendations' => $this->generateRecommendations($compositeScore, $securityData, $performanceData)
                ]
            ];
            
        } catch (\Exception $e) {
            DB::rollback();
            
            if (isset($analysis)) {
                $analysis->update([
                    'status' => 'failed',
                    'analysis_completed_at' => now()
                ]);
            }
            
            Log::error('فشل في التحليل الشامل', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'فشل في التحليل الشامل: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * تحليل الأداء والـ Core Web Vitals
     */
    protected function analyzePerformance($analysis, $url)
    {
        $auditRun = $this->createAuditRun($analysis->id, 'pagespeed');
        
        try {
            $result = $this->pageSpeedService->analyzeWebsite($url);
            
            if ($result['success']) {
                $data = $result['data'];
                
                $analysis->update([
                    'core_web_vitals' => $data['core_web_vitals'] ?? null,
                    'pagespeed_mobile' => $data['mobile_score'] ?? null,
                    'pagespeed_desktop' => $data['desktop_score'] ?? null,
                    'lighthouse_performance' => $data['lighthouse_scores']['performance'] ?? null,
                    'lighthouse_seo' => $data['lighthouse_scores']['seo'] ?? null,
                    'lighthouse_accessibility' => $data['lighthouse_scores']['accessibility'] ?? null,
                    'lighthouse_best_practices' => $data['lighthouse_scores']['best_practices'] ?? null,
                    'page_size_mb' => isset($data['network_performance']['total_byte_weight']) ? 
                                     round($data['network_performance']['total_byte_weight'] / 1024 / 1024, 2) : null,
                    'total_requests' => $data['network_performance']['dom_size'] ?? null,
                    'http_version' => $data['network_performance']['uses_http2'] ? 'HTTP/2' : 'HTTP/1.1'
                ]);
                
                $auditRun->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'result_data' => $data
                ]);
                
                return $data;
            }
            
            throw new \Exception($result['error'] ?? 'فشل تحليل الأداء');
            
        } catch (\Exception $e) {
            $auditRun->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now()
            ]);
            
            Log::error('فشل تحليل الأداء', [
                'analysis_id' => $analysis->id,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * تحليل التقنيات المستخدمة
     */
    protected function analyzeTechnologies($analysis, $url)
    {
        $auditRun = $this->createAuditRun($analysis->id, 'wappalyzer');
        
        try {
            $result = $this->wappalyzerService->analyzeWebsiteTechnologies($url);
            
            if ($result['success']) {
                $technologies = $result['technologies'];
                
                $analysis->update([
                    'stack_detection' => $technologies,
                    'technologies' => $this->categorizeTechnologies($technologies)
                ]);
                
                $auditRun->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'result_data' => $result
                ]);
                
                return $technologies;
            }
            
            throw new \Exception($result['error'] ?? 'فشل كشف التقنيات');
            
        } catch (\Exception $e) {
            $auditRun->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now()
            ]);
            
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * تحليل الأمان
     */
    protected function analyzeSecurity($analysis, $url)
    {
        $auditRun = $this->createAuditRun($analysis->id, 'security');
        
        try {
            $result = $this->securityService->analyzeWebsiteSecurity($url);
            
            $sslData = $result['ssl_analysis'] ?? [];
            $headersData = $result['security_headers'] ?? [];
            
            $analysis->update([
                'security_headers' => $headersData,
                'has_ssl' => $sslData['has_ssl'] ?? false,
                'ssl_grade' => $sslData['ssl_grade'] ?? null,
                'security_issues' => $result['vulnerability_scan'] ?? []
            ]);
            
            $auditRun->update([
                'status' => 'completed',
                'completed_at' => now(),
                'result_data' => $result
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            $auditRun->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now()
            ]);
            
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * تحليل الـ Metadata والـ SEO
     */
    protected function analyzeMetadata($analysis, $url)
    {
        $auditRun = $this->createAuditRun($analysis->id, 'seo');
        
        try {
            // استخراج الـ metadata من الموقع
            $client = new \GuzzleHttp\Client(['timeout' => 30]);
            $response = $client->get($url);
            $html = $response->getBody()->getContents();
            
            $metadata = $this->extractMetadata($html);
            $seoAnalysis = $this->analyzeSEO($html, $url);
            
            $analysis->update([
                'metadata' => $metadata,
                'open_graph' => $metadata['open_graph'] ?? null,
                'twitter_cards' => $metadata['twitter_cards'] ?? null,
                'schema_org' => $metadata['schema_org'] ?? null,
                'has_robots_txt' => $seoAnalysis['has_robots_txt'] ?? false,
                'has_sitemap' => $seoAnalysis['has_sitemap'] ?? false,
                'canonical_issues' => $seoAnalysis['canonical_issues'] ?? null,
                'indexing_status' => $seoAnalysis['indexing_status'] ?? null
            ]);
            
            $combinedData = array_merge($metadata, $seoAnalysis);
            
            $auditRun->update([
                'status' => 'completed',
                'completed_at' => now(),
                'result_data' => $combinedData
            ]);
            
            return $combinedData;
            
        } catch (\Exception $e) {
            $auditRun->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now()
            ]);
            
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * تحليل Google My Business
     */
    protected function analyzeGoogleMyBusiness($analysis, $businessName, $websiteUrl)
    {
        try {
            // البحث عن العمل في Google Places
            $searchResult = $this->googlePlacesService->searchByBusinessName($businessName);
            
            if (!$searchResult['success'] || empty($searchResult['places'])) {
                return null;
            }
            
            // البحث عن أفضل تطابق مع الموقع
            $matchedPlace = $this->findBestPlaceMatch($searchResult['places'], $websiteUrl);
            
            if (!$matchedPlace) {
                return null;
            }
            
            // الحصول على التفاصيل الكاملة
            $placeDetails = $this->googlePlacesService->getPlaceDetails($matchedPlace['id']);
            
            if ($placeDetails['success']) {
                // حفظ بيانات GMB
                $gmbEntity = $this->googlePlacesService->saveOrUpdateGmbEntity($placeDetails['place']);
                
                // البحث عن المنافسين القريبين
                $competitors = $this->findNearbyCompetitors($gmbEntity);
                
                $analysis->update([
                    'maps_presence_score' => $this->calculateMapsPresenceScore($gmbEntity)
                ]);
                
                return [
                    'gmb_entity' => $gmbEntity,
                    'competitors' => $competitors,
                    'maps_score' => $this->calculateMapsPresenceScore($gmbEntity)
                ];
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('فشل تحليل Google My Business', [
                'business_name' => $businessName,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * حساب النتيجة المركبة
     */
    protected function calculateCompositeScore($analysis, $performanceData, $securityData, $metadataData, $gmbData)
    {
        $scores = [
            'performance' => $this->calculatePerformanceScore($performanceData),
            'security' => $this->calculateSecurityScore($securityData),
            'seo' => $this->calculateSEOScore($metadataData),
            'ux' => $this->calculateUXScore($performanceData, $securityData),
            'maps_presence' => $this->calculateMapsPresenceScore($gmbData['gmb_entity'] ?? null)
        ];
        
        // الحساب المرجح للنتيجة الإجمالية (حسب خريطة الطريق)
        $weights = [
            'seo' => 0.30,         // 30%
            'performance' => 0.25,  // 25%
            'security' => 0.15,     // 15%
            'ux' => 0.15,          // 15%
            'maps_presence' => 0.15 // 15%
        ];
        
        $overall = 0;
        foreach ($scores as $category => $score) {
            $overall += $score * $weights[$category];
        }
        
        $scores['overall'] = round($overall);
        
        return $scores;
    }
    
    /**
     * توليد تحليل الذكاء الاصطناعي
     */
    protected function generateAIInsights($analysis, $performanceData, $securityData, $technologyData, $metadataData)
    {
        try {
            $context = "تحليل شامل للموقع:\n";
            $context .= "الأداء: " . json_encode($performanceData, JSON_UNESCAPED_UNICODE) . "\n";
            $context .= "الأمان: " . json_encode($securityData, JSON_UNESCAPED_UNICODE) . "\n";
            $context .= "التقنيات: " . json_encode($technologyData, JSON_UNESCAPED_UNICODE) . "\n";
            $context .= "SEO: " . json_encode($metadataData, JSON_UNESCAPED_UNICODE);
            
            $prompt = "بناءً على التحليل التقني المرفق، قم بتقديم تحليل شامل ومفصل للموقع يتضمن:\n";
            $prompt .= "1. نقاط القوة الرئيسية\n";
            $prompt .= "2. المجالات التي تحتاج تحسين\n";
            $prompt .= "3. توصيات تقنية محددة\n";
            $prompt .= "4. أولويات التحسين\n";
            $prompt .= "5. التوقعات والفرص المستقبلية\n\n";
            $prompt .= $context;
            
            return $this->aiService->generateAdvancedAnalysis($prompt);
            
        } catch (\Exception $e) {
            Log::error('فشل في توليد تحليل الذكاء الاصطناعي', [
                'analysis_id' => $analysis->id,
                'error' => $e->getMessage()
            ]);
            
            return "فشل في توليد التحليل الذكي: " . $e->getMessage();
        }
    }
    
    // Helper Methods
    protected function createAuditRun($analysisId, $auditType)
    {
        return AuditRun::create([
            'website_analysis_id' => $analysisId,
            'audit_type' => $auditType,
            'status' => 'running',
            'started_at' => now()
        ]);
    }
    
    protected function categorizeTechnologies($technologies)
    {
        // تصنيف التقنيات حسب النوع
        return [
            'frontend_count' => count($technologies['frontend'] ?? []),
            'backend_count' => count($technologies['backend'] ?? []),
            'cms_detected' => !empty($technologies['cms']),
            'analytics_tools' => count($technologies['analytics'] ?? []),
            'security_tools' => count($technologies['security'] ?? [])
        ];
    }
    
    protected function extractMetadata($html)
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        
        return [
            'title' => $this->getMetaContent($xpath, '//title'),
            'description' => $this->getMetaContent($xpath, '//meta[@name="description"]/@content'),
            'keywords' => $this->getMetaContent($xpath, '//meta[@name="keywords"]/@content'),
            'h1_tags' => $this->getHeadingTags($xpath, '//h1'),
            'h2_tags' => $this->getHeadingTags($xpath, '//h2'),
            'h3_tags' => $this->getHeadingTags($xpath, '//h3'),
            'open_graph' => $this->extractOpenGraph($xpath),
            'twitter_cards' => $this->extractTwitterCards($xpath),
            'schema_org' => $this->extractSchemaOrg($html)
        ];
    }
    
    protected function analyzeSEO($html, $url)
    {
        // تحليل SEO
        return [
            'has_robots_txt' => $this->checkRobotsTxt($url),
            'has_sitemap' => $this->checkSitemap($url),
            'canonical_issues' => $this->checkCanonicalTags($html),
            'indexing_status' => $this->checkIndexingDirectives($html)
        ];
    }
    
    // Score calculation methods...
    protected function calculatePerformanceScore($performanceData)
    {
        if (isset($performanceData['error'])) return 0;
        
        $mobileScore = $performanceData['mobile_score'] ?? 0;
        $desktopScore = $performanceData['desktop_score'] ?? 0;
        
        return round(($mobileScore * 0.6) + ($desktopScore * 0.4)); // وزن أكبر للموبايل
    }
    
    protected function calculateSecurityScore($securityData)
    {
        if (isset($securityData['error'])) return 0;
        
        $score = 0;
        $sslScore = $securityData['ssl_analysis']['has_ssl'] ?? false ? 40 : 0;
        $headersScore = 0;
        
        $headers = $securityData['security_headers'] ?? [];
        foreach ($headers as $header => $data) {
            if (isset($data['score'])) {
                $headersScore += $data['score'];
            }
        }
        
        return min(round($sslScore + ($headersScore / 10)), 100);
    }
    
    protected function calculateSEOScore($metadataData)
    {
        if (isset($metadataData['error'])) return 0;
        
        $score = 0;
        
        // العنوان والوصف
        if (!empty($metadataData['title'])) $score += 20;
        if (!empty($metadataData['description'])) $score += 20;
        
        // الهيدر تاجز
        if (!empty($metadataData['h1_tags'])) $score += 15;
        if (!empty($metadataData['h2_tags'])) $score += 10;
        
        // Open Graph
        if (!empty($metadataData['open_graph'])) $score += 15;
        
        // Schema.org
        if (!empty($metadataData['schema_org'])) $score += 10;
        
        // Robots و Sitemap
        if ($metadataData['has_robots_txt'] ?? false) $score += 5;
        if ($metadataData['has_sitemap'] ?? false) $score += 5;
        
        return min($score, 100);
    }
    
    protected function calculateUXScore($performanceData, $securityData)
    {
        $performanceScore = $this->calculatePerformanceScore($performanceData);
        $securityScore = $this->calculateSecurityScore($securityData);
        
        return round(($performanceScore * 0.7) + ($securityScore * 0.3));
    }
    
    protected function calculateMapsPresenceScore($gmbEntity)
    {
        if (!$gmbEntity) return 0;
        
        $score = 40; // نقاط أساسية للوجود
        
        if ($gmbEntity->rating && $gmbEntity->rating >= 4.0) $score += 20;
        if ($gmbEntity->total_reviews >= 50) $score += 15;
        if ($gmbEntity->is_verified) $score += 15;
        if ($gmbEntity->photos && count($gmbEntity->photos) >= 5) $score += 10;
        
        return min($score, 100);
    }
    
    protected function generateRecommendations($scores, $securityData, $performanceData)
    {
        $recommendations = [];
        
        if ($scores['performance'] < 70) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'performance',
                'title' => 'تحسين أداء الموقع',
                'description' => 'الموقع يحتاج تحسين في السرعة وCore Web Vitals'
            ];
        }
        
        if ($scores['security'] < 60) {
            $recommendations[] = [
                'priority' => 'critical',
                'category' => 'security',
                'title' => 'تحسين الأمان',
                'description' => 'إضافة Security Headers وتحسين SSL'
            ];
        }
        
        if ($scores['seo'] < 80) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'seo',
                'title' => 'تحسين SEO',
                'description' => 'تحسين الـ metadata والهيكل'
            ];
        }
        
        return $recommendations;
    }
    
    // Additional helper methods for metadata extraction...
    protected function getMetaContent($xpath, $query)
    {
        $nodes = $xpath->query($query);
        return $nodes->length > 0 ? trim($nodes->item(0)->textContent ?? $nodes->item(0)->nodeValue) : null;
    }
    
    protected function getHeadingTags($xpath, $query)
    {
        $headings = [];
        $nodes = $xpath->query($query);
        
        foreach ($nodes as $node) {
            $headings[] = trim($node->textContent);
        }
        
        return $headings;
    }
    
    protected function extractOpenGraph($xpath)
    {
        $og = [];
        $nodes = $xpath->query('//meta[starts-with(@property, "og:")]');
        
        foreach ($nodes as $node) {
            $property = $node->getAttribute('property');
            $content = $node->getAttribute('content');
            $og[str_replace('og:', '', $property)] = $content;
        }
        
        return $og;
    }
    
    protected function extractTwitterCards($xpath)
    {
        $twitter = [];
        $nodes = $xpath->query('//meta[starts-with(@name, "twitter:")]');
        
        foreach ($nodes as $node) {
            $name = $node->getAttribute('name');
            $content = $node->getAttribute('content');
            $twitter[str_replace('twitter:', '', $name)] = $content;
        }
        
        return $twitter;
    }
    
    protected function extractSchemaOrg($html)
    {
        $schemas = [];
        
        if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            foreach ($matches[1] as $jsonLd) {
                $decoded = json_decode($jsonLd, true);
                if ($decoded) {
                    $schemas[] = $decoded;
                }
            }
        }
        
        return $schemas;
    }
    
    protected function checkRobotsTxt($url)
    {
        $robotsUrl = rtrim($url, '/') . '/robots.txt';
        
        try {
            $client = new \GuzzleHttp\Client(['timeout' => 10]);
            $response = $client->get($robotsUrl);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    protected function checkSitemap($url)
    {
        $sitemapUrls = [
            rtrim($url, '/') . '/sitemap.xml',
            rtrim($url, '/') . '/sitemap_index.xml'
        ];
        
        foreach ($sitemapUrls as $sitemapUrl) {
            try {
                $client = new \GuzzleHttp\Client(['timeout' => 10]);
                $response = $client->get($sitemapUrl);
                if ($response->getStatusCode() === 200) {
                    return true;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        return false;
    }
    
    protected function checkCanonicalTags($html)
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        
        $canonicals = $xpath->query('//link[@rel="canonical"]');
        
        return [
            'has_canonical' => $canonicals->length > 0,
            'multiple_canonicals' => $canonicals->length > 1,
            'canonical_url' => $canonicals->length > 0 ? $canonicals->item(0)->getAttribute('href') : null
        ];
    }
    
    protected function checkIndexingDirectives($html)
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        
        $robotsMeta = $xpath->query('//meta[@name="robots"]');
        
        if ($robotsMeta->length > 0) {
            $content = $robotsMeta->item(0)->getAttribute('content');
            return [
                'robots_meta' => $content,
                'noindex' => stripos($content, 'noindex') !== false,
                'nofollow' => stripos($content, 'nofollow') !== false
            ];
        }
        
        return ['robots_meta' => null, 'noindex' => false, 'nofollow' => false];
    }
    
    protected function findBestPlaceMatch($places, $websiteUrl)
    {
        $domain = parse_url($websiteUrl, PHP_URL_HOST);
        
        foreach ($places as $place) {
            $placeWebsite = $place['websiteUri'] ?? '';
            if ($placeWebsite) {
                $placeDomain = parse_url($placeWebsite, PHP_URL_HOST);
                if ($placeDomain === $domain) {
                    return $place;
                }
            }
        }
        
        // إذا لم نجد تطابق مباشر، نأخذ أول نتيجة
        return $places[0] ?? null;
    }
    
    protected function findNearbyCompetitors($gmbEntity)
    {
        if (!$gmbEntity || !$gmbEntity->latitude || !$gmbEntity->longitude) {
            return [];
        }
        
        $competitorsResult = $this->googlePlacesService->findNearbyCompetitors(
            $gmbEntity->latitude,
            $gmbEntity->longitude,
            $gmbEntity->types ?? [],
            5000 // 5km radius
        );
        
        if ($competitorsResult['success']) {
            $competitors = [];
            
            foreach ($competitorsResult['competitors'] as $competitorPlace) {
                if ($competitorPlace['id'] !== $gmbEntity->place_id) {
                    $competitor = Competitor::updateOrCreate([
                        'main_place_id' => $gmbEntity->place_id,
                        'competitor_place_id' => $competitorPlace['id']
                    ], [
                        'competitor_name' => $competitorPlace['displayName']['text'] ?? '',
                        'competitor_website' => $competitorPlace['websiteUri'] ?? null,
                        'latitude' => $competitorPlace['location']['latitude'] ?? null,
                        'longitude' => $competitorPlace['location']['longitude'] ?? null,
                        'competitor_rating' => $competitorPlace['rating'] ?? null,
                        'competitor_reviews' => $competitorPlace['userRatingCount'] ?? 0,
                        'discovery_method' => 'google_nearby',
                        'is_direct_competitor' => true
                    ]);
                    
                    $competitors[] = $competitor;
                }
            }
            
            return $competitors;
        }
        
        return [];
    }
}