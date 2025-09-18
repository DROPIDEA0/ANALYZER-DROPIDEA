<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class PageSpeedService
{
    protected $client;
    protected $apiKey;
    protected $baseUrl = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
    
    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 60,
            'headers' => [
                'User-Agent' => 'AnalyzerDropidea/1.0'
            ]
        ]);
        
        $this->apiKey = config('services.google.pagespeed_api_key', env('GOOGLE_PAGESPEED_API_KEY'));
    }
    
    /**
     * تحليل أداء الموقع للموبايل والديسكتوب
     */
    public function analyzeWebsite($url)
    {
        $results = [
            'mobile' => $this->runPageSpeedTest($url, 'mobile'),
            'desktop' => $this->runPageSpeedTest($url, 'desktop')
        ];
        
        return [
            'success' => $results['mobile']['success'] || $results['desktop']['success'],
            'data' => $this->combineResults($results),
            'raw_data' => $results
        ];
    }
    
    /**
     * تشغيل اختبار PageSpeed لاستراتيجية محددة
     */
    protected function runPageSpeedTest($url, $strategy = 'mobile')
    {
        try {
            $params = [
                'url' => $url,
                'strategy' => $strategy,
                'category' => ['PERFORMANCE', 'SEO', 'ACCESSIBILITY', 'BEST_PRACTICES'],
                'locale' => 'ar'
            ];
            
            if ($this->apiKey) {
                $params['key'] = $this->apiKey;
            }
            
            $response = $this->client->get($this->baseUrl, [
                'query' => $params
            ]);
            
            $data = json_decode($response->getBody(), true);
            
            return [
                'success' => true,
                'data' => $this->extractPageSpeedData($data, $strategy)
            ];
            
        } catch (GuzzleException $e) {
            Log::error('PageSpeed API error', [
                'url' => $url,
                'strategy' => $strategy,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'فشل في تحليل السرعة: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * استخراج البيانات المهمة من نتيجة PageSpeed
     */
    protected function extractPageSpeedData($rawData, $strategy)
    {
        $lighthouse = $rawData['lighthouseResult'] ?? [];
        $audits = $lighthouse['audits'] ?? [];
        
        // Core Web Vitals
        $coreWebVitals = [
            'lcp' => $this->extractMetric($audits, 'largest-contentful-paint'),
            'fid' => $this->extractMetric($audits, 'max-potential-fid'),
            'cls' => $this->extractMetric($audits, 'cumulative-layout-shift'),
            'fcp' => $this->extractMetric($audits, 'first-contentful-paint'),
            'inp' => $this->extractMetric($audits, 'interaction-to-next-paint')
        ];
        
        // Lighthouse Scores
        $categories = $lighthouse['categories'] ?? [];
        $scores = [
            'performance' => round(($categories['performance']['score'] ?? 0) * 100),
            'seo' => round(($categories['seo']['score'] ?? 0) * 100),
            'accessibility' => round(($categories['accessibility']['score'] ?? 0) * 100),
            'best_practices' => round(($categories['best-practices']['score'] ?? 0) * 100)
        ];
        
        // Network Performance
        $networkMetrics = [
            'total_byte_weight' => $this->extractNumericValue($audits, 'total-byte-weight'),
            'dom_size' => $this->extractNumericValue($audits, 'dom-size'),
            'uses_http2' => $this->checkAuditPassed($audits, 'uses-http2'),
            'uses_text_compression' => $this->checkAuditPassed($audits, 'uses-text-compression'),
            'render_blocking_resources' => $this->extractNumericValue($audits, 'render-blocking-resources')
        ];
        
        // Performance Opportunities
        $opportunities = [];
        foreach ($audits as $auditKey => $auditData) {
            if (isset($auditData['details']['overallSavingsMs']) && $auditData['details']['overallSavingsMs'] > 100) {
                $opportunities[] = [
                    'audit' => $auditKey,
                    'title' => $auditData['title'] ?? $auditKey,
                    'description' => $auditData['description'] ?? '',
                    'savings_ms' => $auditData['details']['overallSavingsMs'],
                    'savings_bytes' => $auditData['details']['overallSavingsBytes'] ?? null
                ];
            }
        }
        
        return [
            'strategy' => $strategy,
            'core_web_vitals' => $coreWebVitals,
            'lighthouse_scores' => $scores,
            'network_metrics' => $networkMetrics,
            'opportunities' => $opportunities,
            'raw_performance_score' => $scores['performance']
        ];
    }
    
    /**
     * استخراج قيمة المترك
     */
    protected function extractMetric($audits, $metricKey)
    {
        $audit = $audits[$metricKey] ?? null;
        if (!$audit) return null;
        
        return [
            'value' => $audit['numericValue'] ?? null,
            'displayValue' => $audit['displayValue'] ?? null,
            'score' => $audit['score'] ?? null
        ];
    }
    
    /**
     * استخراج قيمة رقمية
     */
    protected function extractNumericValue($audits, $auditKey)
    {
        return $audits[$auditKey]['numericValue'] ?? null;
    }
    
    /**
     * فحص ما إذا كان التدقيق ناجحاً
     */
    protected function checkAuditPassed($audits, $auditKey)
    {
        $audit = $audits[$auditKey] ?? null;
        return $audit ? ($audit['score'] >= 0.9) : false;
    }
    
    /**
     * دمج نتائج الموبايل والديسكتوب
     */
    protected function combineResults($results)
    {
        $mobile = $results['mobile']['data'] ?? null;
        $desktop = $results['desktop']['data'] ?? null;
        
        if (!$mobile && !$desktop) {
            return null;
        }
        
        $combined = [
            'mobile_score' => $mobile['lighthouse_scores']['performance'] ?? null,
            'desktop_score' => $desktop['lighthouse_scores']['performance'] ?? null,
            'core_web_vitals' => $mobile['core_web_vitals'] ?? $desktop['core_web_vitals'] ?? [],
            'lighthouse_scores' => [
                'performance' => max(
                    $mobile['lighthouse_scores']['performance'] ?? 0,
                    $desktop['lighthouse_scores']['performance'] ?? 0
                ),
                'seo' => max(
                    $mobile['lighthouse_scores']['seo'] ?? 0,
                    $desktop['lighthouse_scores']['seo'] ?? 0
                ),
                'accessibility' => max(
                    $mobile['lighthouse_scores']['accessibility'] ?? 0,
                    $desktop['lighthouse_scores']['accessibility'] ?? 0
                ),
                'best_practices' => max(
                    $mobile['lighthouse_scores']['best_practices'] ?? 0,
                    $desktop['lighthouse_scores']['best_practices'] ?? 0
                )
            ],
            'network_performance' => $this->combineNetworkMetrics($mobile, $desktop),
            'opportunities' => array_merge(
                $mobile['opportunities'] ?? [],
                $desktop['opportunities'] ?? []
            )
        ];
        
        return $combined;
    }
    
    /**
     * دمج مقاييس الشبكة
     */
    protected function combineNetworkMetrics($mobile, $desktop)
    {
        $mobileNet = $mobile['network_metrics'] ?? [];
        $desktopNet = $desktop['network_metrics'] ?? [];
        
        return [
            'total_byte_weight' => max($mobileNet['total_byte_weight'] ?? 0, $desktopNet['total_byte_weight'] ?? 0),
            'dom_size' => max($mobileNet['dom_size'] ?? 0, $desktopNet['dom_size'] ?? 0),
            'uses_http2' => ($mobileNet['uses_http2'] ?? false) || ($desktopNet['uses_http2'] ?? false),
            'uses_text_compression' => ($mobileNet['uses_text_compression'] ?? false) || ($desktopNet['uses_text_compression'] ?? false),
            'render_blocking_resources' => max($mobileNet['render_blocking_resources'] ?? 0, $desktopNet['render_blocking_resources'] ?? 0)
        ];
    }
}