<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use DOMDocument;
use DOMXPath;

class PerformanceAnalyzerService
{
    /**
     * تحليل أداء الموقع
     */
    public function analyzePerformance($url)
    {
        try {
            $performanceData = [
                'load_time' => $this->measureLoadTime($url),
                'page_size' => $this->measurePageSize($url),
                'resources' => $this->analyzeResources($url),
                'compression' => $this->checkCompression($url),
                'caching' => $this->analyzeCaching($url),
                'mobile_performance' => $this->analyzeMobilePerformance($url),
                'server_response' => $this->analyzeServerResponse($url),
            ];

            // حساب النقاط الإجمالية
            $score = $this->calculatePerformanceScore($performanceData);

            return [
                'score' => $score,
                'grade' => $this->getGrade($score),
                'load_time' => $performanceData['load_time'],
                'page_size' => $performanceData['page_size'],
                'details' => $performanceData,
                'recommendations' => $this->generateRecommendations($performanceData),
                'metrics' => [
                    'first_contentful_paint' => $this->estimateFirstContentfulPaint($performanceData),
                    'largest_contentful_paint' => $this->estimateLargestContentfulPaint($performanceData),
                    'cumulative_layout_shift' => $this->estimateCumulativeLayoutShift($performanceData),
                    'time_to_interactive' => $this->estimateTimeToInteractive($performanceData),
                ]
            ];

        } catch (\Exception $e) {
            throw new \Exception('خطأ في تحليل الأداء: ' . $e->getMessage());
        }
    }

    /**
     * قياس وقت التحميل
     */
    private function measureLoadTime($url)
    {
        $times = [];
        
        // قياس وقت التحميل 3 مرات وأخذ المتوسط
        for ($i = 0; $i < 3; $i++) {
            $start = microtime(true);
            
            try {
                $response = Http::timeout(30)->get($url);
                $end = microtime(true);
                
                if ($response->successful()) {
                    $times[] = ($end - $start);
                }
            } catch (\Exception $e) {
                // تجاهل الأخطاء وحاول مرة أخرى
            }
            
            // انتظار قصير بين القياسات
            usleep(500000); // 0.5 ثانية
        }
        
        if (empty($times)) {
            throw new \Exception('لا يمكن قياس وقت التحميل');
        }
        
        return round(array_sum($times) / count($times), 2);
    }

    /**
     * قياس حجم الصفحة
     */
    private function measurePageSize($url)
    {
        try {
            $response = Http::timeout(30)->get($url);
            
            if (!$response->successful()) {
                throw new \Exception('لا يمكن الوصول إلى الموقع');
            }

            $html = $response->body();
            $htmlSize = strlen($html);
            
            // تحليل الموارد
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            
            $totalSize = $htmlSize;
            $resourceSizes = [
                'html' => $htmlSize,
                'css' => 0,
                'js' => 0,
                'images' => 0,
                'fonts' => 0,
                'other' => 0
            ];
            
            // تحليل ملفات CSS
            $cssNodes = $xpath->query('//link[@rel="stylesheet"]/@href');
            foreach ($cssNodes as $node) {
                $cssUrl = $this->resolveUrl($url, $node->textContent);
                $size = $this->getResourceSize($cssUrl);
                $resourceSizes['css'] += $size;
                $totalSize += $size;
            }
            
            // تحليل ملفات JavaScript
            $jsNodes = $xpath->query('//script[@src]/@src');
            foreach ($jsNodes as $node) {
                $jsUrl = $this->resolveUrl($url, $node->textContent);
                $size = $this->getResourceSize($jsUrl);
                $resourceSizes['js'] += $size;
                $totalSize += $size;
            }
            
            // تحليل الصور
            $imgNodes = $xpath->query('//img[@src]/@src');
            foreach ($imgNodes as $node) {
                $imgUrl = $this->resolveUrl($url, $node->textContent);
                $size = $this->getResourceSize($imgUrl);
                $resourceSizes['images'] += $size;
                $totalSize += $size;
            }
            
            return [
                'total_size' => $totalSize,
                'total_size_mb' => round($totalSize / (1024 * 1024), 2),
                'breakdown' => $resourceSizes,
                'is_optimized' => $totalSize < 2 * 1024 * 1024, // أقل من 2 ميجابايت
            ];
            
        } catch (\Exception $e) {
            return [
                'total_size' => 0,
                'total_size_mb' => 0,
                'breakdown' => [],
                'is_optimized' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * تحليل الموارد
     */
    private function analyzeResources($url)
    {
        try {
            $response = Http::timeout(30)->get($url);
            $html = $response->body();
            
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            
            $resources = [
                'css_files' => $xpath->query('//link[@rel="stylesheet"]')->length,
                'js_files' => $xpath->query('//script[@src]')->length,
                'images' => $xpath->query('//img')->length,
                'external_resources' => 0,
                'inline_css' => $xpath->query('//style')->length,
                'inline_js' => $xpath->query('//script[not(@src)]')->length,
            ];
            
            // عد الموارد الخارجية
            $allLinks = $xpath->query('//link[@href] | //script[@src] | //img[@src]');
            $baseDomain = parse_url($url, PHP_URL_HOST);
            
            foreach ($allLinks as $link) {
                $href = $link->getAttribute('href') ?: $link->getAttribute('src');
                if (strpos($href, 'http') === 0) {
                    $linkDomain = parse_url($href, PHP_URL_HOST);
                    if ($linkDomain !== $baseDomain) {
                        $resources['external_resources']++;
                    }
                }
            }
            
            return [
                'counts' => $resources,
                'is_optimized' => $resources['css_files'] <= 5 && $resources['js_files'] <= 10,
                'has_too_many_requests' => ($resources['css_files'] + $resources['js_files'] + $resources['images']) > 50,
            ];
            
        } catch (\Exception $e) {
            return [
                'counts' => [],
                'is_optimized' => false,
                'has_too_many_requests' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * فحص الضغط
     */
    private function checkCompression($url)
    {
        try {
            $response = Http::withHeaders([
                'Accept-Encoding' => 'gzip, deflate, br'
            ])->timeout(15)->get($url);
            
            $headers = $response->headers();
            
            return [
                'gzip_enabled' => isset($headers['content-encoding']) && 
                                in_array('gzip', $headers['content-encoding']),
                'brotli_enabled' => isset($headers['content-encoding']) && 
                                  in_array('br', $headers['content-encoding']),
                'compression_ratio' => $this->calculateCompressionRatio($response),
            ];
            
        } catch (\Exception $e) {
            return [
                'gzip_enabled' => false,
                'brotli_enabled' => false,
                'compression_ratio' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * تحليل التخزين المؤقت
     */
    private function analyzeCaching($url)
    {
        try {
            $response = Http::timeout(15)->get($url);
            $headers = $response->headers();
            
            $cacheControl = $headers['cache-control'][0] ?? '';
            $expires = $headers['expires'][0] ?? '';
            $etag = $headers['etag'][0] ?? '';
            $lastModified = $headers['last-modified'][0] ?? '';
            
            return [
                'has_cache_control' => !empty($cacheControl),
                'has_expires' => !empty($expires),
                'has_etag' => !empty($etag),
                'has_last_modified' => !empty($lastModified),
                'cache_control' => $cacheControl,
                'is_cacheable' => strpos($cacheControl, 'no-cache') === false && 
                                strpos($cacheControl, 'no-store') === false,
            ];
            
        } catch (\Exception $e) {
            return [
                'has_cache_control' => false,
                'has_expires' => false,
                'has_etag' => false,
                'has_last_modified' => false,
                'is_cacheable' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * تحليل الأداء على الأجهزة المحمولة
     */
    private function analyzeMobilePerformance($url)
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15'
            ])->timeout(30)->get($url);
            
            $html = $response->body();
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            
            // فحص viewport
            $viewportNodes = $xpath->query('//meta[@name="viewport"]');
            $hasViewport = $viewportNodes->length > 0;
            
            // فحص الخطوط المناسبة للأجهزة المحمولة
            $fontSizes = $this->analyzeFontSizes($html);
            
            return [
                'has_viewport' => $hasViewport,
                'is_responsive' => $hasViewport && $this->checkResponsiveDesign($html),
                'font_sizes' => $fontSizes,
                'touch_friendly' => $this->checkTouchFriendly($xpath),
                'mobile_load_time' => $this->measureLoadTime($url), // يمكن تحسينه لمحاكاة شبكة بطيئة
            ];
            
        } catch (\Exception $e) {
            return [
                'has_viewport' => false,
                'is_responsive' => false,
                'font_sizes' => [],
                'touch_friendly' => false,
                'mobile_load_time' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * تحليل استجابة الخادم
     */
    private function analyzeServerResponse($url)
    {
        try {
            $start = microtime(true);
            $response = Http::timeout(15)->get($url);
            $end = microtime(true);
            
            $ttfb = ($end - $start) * 1000; // Time to First Byte بالميلي ثانية
            
            $headers = $response->headers();
            
            return [
                'status_code' => $response->status(),
                'ttfb' => round($ttfb, 2),
                'server' => $headers['server'][0] ?? 'غير محدد',
                'is_fast_ttfb' => $ttfb < 200, // أقل من 200ms
                'has_cdn' => $this->detectCDN($headers),
                'http_version' => $this->detectHttpVersion($response),
            ];
            
        } catch (\Exception $e) {
            return [
                'status_code' => 0,
                'ttfb' => 0,
                'server' => 'غير محدد',
                'is_fast_ttfb' => false,
                'has_cdn' => false,
                'http_version' => 'غير محدد',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * حساب نقاط الأداء
     */
    private function calculatePerformanceScore($performanceData)
    {
        $score = 0;
        
        // وقت التحميل (30 نقطة)
        if ($performanceData['load_time'] < 1) $score += 30;
        elseif ($performanceData['load_time'] < 2) $score += 25;
        elseif ($performanceData['load_time'] < 3) $score += 20;
        elseif ($performanceData['load_time'] < 5) $score += 15;
        else $score += 5;
        
        // حجم الصفحة (20 نقطة)
        if (isset($performanceData['page_size']['is_optimized']) && $performanceData['page_size']['is_optimized']) {
            $score += 20;
        } elseif (isset($performanceData['page_size']['total_size_mb']) && $performanceData['page_size']['total_size_mb'] < 5) {
            $score += 15;
        } else {
            $score += 5;
        }
        
        // الضغط (15 نقطة)
        if (isset($performanceData['compression']['gzip_enabled']) && $performanceData['compression']['gzip_enabled']) {
            $score += 10;
        }
        if (isset($performanceData['compression']['brotli_enabled']) && $performanceData['compression']['brotli_enabled']) {
            $score += 5;
        }
        
        // التخزين المؤقت (15 نقطة)
        if (isset($performanceData['caching']['is_cacheable']) && $performanceData['caching']['is_cacheable']) {
            $score += 15;
        }
        
        // الأداء على الأجهزة المحمولة (10 نقاط)
        if (isset($performanceData['mobile_performance']['is_responsive']) && $performanceData['mobile_performance']['is_responsive']) {
            $score += 10;
        }
        
        // استجابة الخادم (10 نقاط)
        if (isset($performanceData['server_response']['is_fast_ttfb']) && $performanceData['server_response']['is_fast_ttfb']) {
            $score += 10;
        }
        
        return min($score, 100);
    }

    /**
     * تحديد الدرجة
     */
    private function getGrade($score)
    {
        if ($score >= 90) return 'ممتاز';
        if ($score >= 80) return 'جيد جداً';
        if ($score >= 70) return 'جيد';
        if ($score >= 60) return 'مقبول';
        return 'يحتاج تحسين';
    }

    /**
     * إنشاء التوصيات
     */
    private function generateRecommendations($performanceData)
    {
        $recommendations = [];
        
        if ($performanceData['load_time'] > 3) {
            $recommendations[] = 'تحسين سرعة التحميل - الهدف أقل من 3 ثوانٍ';
        }
        
        if (isset($performanceData['page_size']['total_size_mb']) && $performanceData['page_size']['total_size_mb'] > 2) {
            $recommendations[] = 'تقليل حجم الصفحة عبر ضغط الصور والملفات';
        }
        
        if (!isset($performanceData['compression']['gzip_enabled']) || !$performanceData['compression']['gzip_enabled']) {
            $recommendations[] = 'تفعيل ضغط GZIP على الخادم';
        }
        
        if (!isset($performanceData['caching']['is_cacheable']) || !$performanceData['caching']['is_cacheable']) {
            $recommendations[] = 'تحسين إعدادات التخزين المؤقت';
        }
        
        if (!isset($performanceData['mobile_performance']['is_responsive']) || !$performanceData['mobile_performance']['is_responsive']) {
            $recommendations[] = 'تحسين التصميم للأجهزة المحمولة';
        }
        
        if (isset($performanceData['resources']['has_too_many_requests']) && $performanceData['resources']['has_too_many_requests']) {
            $recommendations[] = 'تقليل عدد طلبات HTTP عبر دمج الملفات';
        }
        
        return $recommendations;
    }

    // Helper methods
    
    private function resolveUrl($baseUrl, $relativeUrl)
    {
        if (strpos($relativeUrl, 'http') === 0) {
            return $relativeUrl;
        }
        
        $base = parse_url($baseUrl);
        $scheme = $base['scheme'] ?? 'http';
        $host = $base['host'] ?? '';
        
        if (strpos($relativeUrl, '//') === 0) {
            return $scheme . ':' . $relativeUrl;
        }
        
        if (strpos($relativeUrl, '/') === 0) {
            return $scheme . '://' . $host . $relativeUrl;
        }
        
        return $scheme . '://' . $host . '/' . $relativeUrl;
    }
    
    private function getResourceSize($url)
    {
        try {
            $response = Http::timeout(10)->head($url);
            $headers = $response->headers();
            return isset($headers['content-length']) ? (int)$headers['content-length'][0] : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    private function calculateCompressionRatio($response)
    {
        $headers = $response->headers();
        if (isset($headers['content-length']) && isset($headers['content-encoding'])) {
            // تقدير تقريبي لنسبة الضغط
            return 0.7; // 70% ضغط تقريبي
        }
        return 0;
    }
    
    private function analyzeFontSizes($html)
    {
        // تحليل مبسط لأحجام الخطوط
        preg_match_all('/font-size:\s*(\d+)px/i', $html, $matches);
        $fontSizes = array_map('intval', $matches[1]);
        
        return [
            'min_size' => !empty($fontSizes) ? min($fontSizes) : 0,
            'max_size' => !empty($fontSizes) ? max($fontSizes) : 0,
            'avg_size' => !empty($fontSizes) ? round(array_sum($fontSizes) / count($fontSizes)) : 0,
            'is_readable' => !empty($fontSizes) ? min($fontSizes) >= 14 : false,
        ];
    }
    
    private function checkResponsiveDesign($html)
    {
        // فحص مبسط للتصميم المتجاوب
        return strpos($html, '@media') !== false || 
               strpos($html, 'responsive') !== false ||
               strpos($html, 'viewport') !== false;
    }
    
    private function checkTouchFriendly($xpath)
    {
        // فحص العناصر القابلة للمس
        $buttons = $xpath->query('//button | //a | //input[@type="submit"]');
        return $buttons->length > 0; // تحليل مبسط
    }
    
    private function detectCDN($headers)
    {
        $cdnHeaders = ['cf-ray', 'x-cache', 'x-served-by', 'x-amz-cf-id'];
        
        foreach ($cdnHeaders as $header) {
            if (isset($headers[$header])) {
                return true;
            }
        }
        
        return false;
    }
    
    private function detectHttpVersion($response)
    {
        // محاولة اكتشاف إصدار HTTP
        return 'HTTP/1.1'; // افتراضي
    }
    
    // تقدير مقاييس الأداء الأساسية
    
    private function estimateFirstContentfulPaint($performanceData)
    {
        return round($performanceData['load_time'] * 0.3, 2);
    }
    
    private function estimateLargestContentfulPaint($performanceData)
    {
        return round($performanceData['load_time'] * 0.7, 2);
    }
    
    private function estimateCumulativeLayoutShift($performanceData)
    {
        // تقدير مبسط
        return 0.1;
    }
    
    private function estimateTimeToInteractive($performanceData)
    {
        return round($performanceData['load_time'] * 1.2, 2);
    }
}
