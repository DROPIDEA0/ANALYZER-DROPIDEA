<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;

class ComprehensiveAnalysisService
{
    protected $client;
    
    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'verify' => true,
            'headers' => [
                'User-Agent' => 'AnalyzerDropidea Real Website Scanner/2.0'
            ]
        ]);
    }
    
    /**
     * فحص شامل وحقيقي للموقع
     */
    public function performComprehensiveAnalysis($url)
    {
        try {
            $response = $this->client->get($url);
            $html = $response->getBody()->getContents();
            
            return [
                'website_structure' => $this->analyzeWebsiteStructure($html, $url),
                'technical_analysis' => $this->analyzeTechnicalStack($html, $response),
                'content_analysis' => $this->analyzeContentQuality($html),
                'file_analysis' => $this->analyzeWebsiteFiles($url),
                'recommendations' => $this->generateSpecificRecommendations($html, $url)
            ];
            
        } catch (\Exception $e) {
            Log::error('Comprehensive Analysis Error', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            
            return [
                'error' => 'فشل في الوصول للموقع: ' . $e->getMessage(),
                'suggestions' => [
                    'تأكد من صحة رابط الموقع',
                    'تحقق من أن الموقع متاح للعامة',
                    'تأكد من وجود اتصال بالإنترنت'
                ]
            ];
        }
    }
    
    /**
     * تحليل بنية الموقع الفعلية
     */
    private function analyzeWebsiteStructure($html, $url)
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        
        return [
            'has_navigation' => $this->checkNavigation($xpath),
            'has_footer' => $this->checkFooter($xpath),
            'has_header' => $this->checkHeader($xpath),
            'page_structure' => $this->analyzePageStructure($xpath),
            'internal_links' => $this->countInternalLinks($xpath, $url),
            'external_links' => $this->countExternalLinks($xpath, $url),
            'images_count' => $this->countImages($xpath),
            'missing_alt_texts' => $this->findMissingAltTexts($xpath)
        ];
    }
    
    /**
     * تحليل التقنيات المستخدمة
     */
    private function analyzeTechnicalStack($html, $response)
    {
        $headers = $response->getHeaders();
        
        return [
            'server_technology' => $this->detectServerTechnology($headers),
            'cms_detection' => $this->detectCMS($html),
            'frameworks' => $this->detectFrameworks($html),
            'javascript_libraries' => $this->detectJSLibraries($html),
            'css_frameworks' => $this->detectCSSFrameworks($html),
            'analytics_tools' => $this->detectAnalyticsTools($html),
            'caching_headers' => $this->analyzeCachingHeaders($headers)
        ];
    }
    
    /**
     * تحليل جودة المحتوى
     */
    private function analyzeContentQuality($html)
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        
        return [
            'title_analysis' => $this->analyzeTitleTag($xpath),
            'meta_description' => $this->analyzeMetaDescription($xpath),
            'heading_structure' => $this->analyzeHeadingStructure($xpath),
            'text_content_length' => $this->calculateTextLength($xpath),
            'keyword_density' => $this->analyzeKeywordDensity($xpath),
            'readability_score' => $this->calculateReadabilityScore($xpath)
        ];
    }
    
    /**
     * تحليل الملفات المطلوبة
     */
    private function analyzeWebsiteFiles($url)
    {
        $baseUrl = rtrim($url, '/');
        $files = [
            'robots.txt' => $baseUrl . '/robots.txt',
            'sitemap.xml' => $baseUrl . '/sitemap.xml',
            'favicon.ico' => $baseUrl . '/favicon.ico'
        ];
        
        $results = [];
        foreach ($files as $name => $fileUrl) {
            $results[$name] = $this->checkFileExists($fileUrl);
        }
        
        return $results;
    }
    
    /**
     * توليد توصيات محددة وقابلة للتنفيذ
     */
    private function generateSpecificRecommendations($html, $url)
    {
        $recommendations = [];
        
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        
        // فحص المشاكل الفعلية وإعطاء حلول محددة
        
        // عنوان الصفحة
        $title = $xpath->query('//title')->item(0);
        if (!$title || strlen(trim($title->textContent)) < 30) {
            $recommendations['seo'][] = [
                'issue' => 'عنوان الصفحة قصير جداً أو مفقود',
                'file' => 'index.html أو الملف الرئيسي',
                'fix' => 'أضف عنوان وصفي بين 50-60 حرف في <title>عنوان مميز هنا</title>',
                'priority' => 'عالية'
            ];
        }
        
        // وصف meta
        $metaDesc = $xpath->query('//meta[@name="description"]')->item(0);
        if (!$metaDesc) {
            $recommendations['seo'][] = [
                'issue' => 'وصف meta مفقود',
                'file' => 'head section في HTML',
                'fix' => 'أضف <meta name="description" content="وصف الموقع هنا 150-160 حرف">',
                'priority' => 'عالية'
            ];
        }
        
        // فحص H1
        $h1Tags = $xpath->query('//h1');
        if ($h1Tags->length === 0) {
            $recommendations['seo'][] = [
                'issue' => 'لا يوجد عنوان H1 في الصفحة',
                'file' => 'محتوى الصفحة الرئيسية',
                'fix' => 'أضف <h1>العنوان الرئيسي للصفحة</h1>',
                'priority' => 'متوسطة'
            ];
        } elseif ($h1Tags->length > 1) {
            $recommendations['seo'][] = [
                'issue' => 'يوجد أكثر من H1 في الصفحة',
                'file' => 'محتوى الصفحة',
                'fix' => 'استخدم H1 واحد فقط للعنوان الرئيسي، غير الباقي لـ H2 أو H3',
                'priority' => 'متوسطة'
            ];
        }
        
        // فحص الصور
        $images = $xpath->query('//img[not(@alt) or @alt=""]');
        if ($images->length > 0) {
            $recommendations['accessibility'][] = [
                'issue' => "يوجد {$images->length} صورة بدون نص بديل",
                'file' => 'جميع صفحات HTML التي تحتوي على صور',
                'fix' => 'أضف alt="وصف الصورة" لكل صورة، مثال: <img src="logo.jpg" alt="شعار الشركة">',
                'priority' => 'متوسطة'
            ];
        }
        
        // فحص الروابط الداخلية
        $externalLinks = $xpath->query('//a[starts-with(@href, "http") and not(contains(@href, "' . parse_url($url, PHP_URL_HOST) . '"))]');
        foreach ($externalLinks as $link) {
            if (!$link->hasAttribute('rel') || strpos($link->getAttribute('rel'), 'nofollow') === false) {
                $recommendations['seo'][] = [
                    'issue' => 'روابط خارجية بدون rel="nofollow"',
                    'file' => 'صفحات HTML التي تحتوي على روابط خارجية',
                    'fix' => 'أضف rel="nofollow" للروابط الخارجية: <a href="رابط_خارجي" rel="nofollow">',
                    'priority' => 'منخفضة'
                ];
                break;
            }
        }
        
        return $recommendations;
    }
    
    // Helper methods للفحص الفعلي
    
    private function checkNavigation($xpath)
    {
        $nav = $xpath->query('//nav | //ul[@class*="nav"] | //div[@class*="nav"]');
        return $nav->length > 0;
    }
    
    private function checkFooter($xpath)
    {
        $footer = $xpath->query('//footer | //div[@class*="footer"]');
        return $footer->length > 0;
    }
    
    private function checkHeader($xpath)
    {
        $header = $xpath->query('//header | //div[@class*="header"]');
        return $header->length > 0;
    }
    
    private function analyzePageStructure($xpath)
    {
        return [
            'has_main_content' => $xpath->query('//main | //div[@class*="content"] | //article')->length > 0,
            'has_sidebar' => $xpath->query('//aside | //div[@class*="sidebar"]')->length > 0,
            'semantic_html5' => $xpath->query('//section | //article | //aside | //nav | //header | //footer')->length > 0
        ];
    }
    
    private function countInternalLinks($xpath, $url)
    {
        $domain = parse_url($url, PHP_URL_HOST);
        $links = $xpath->query('//a[contains(@href, "' . $domain . '") or starts-with(@href, "/") or not(starts-with(@href, "http"))]');
        return $links->length;
    }
    
    private function countExternalLinks($xpath, $url)
    {
        $domain = parse_url($url, PHP_URL_HOST);
        $links = $xpath->query('//a[starts-with(@href, "http") and not(contains(@href, "' . $domain . '"))]');
        return $links->length;
    }
    
    private function countImages($xpath)
    {
        return $xpath->query('//img')->length;
    }
    
    private function findMissingAltTexts($xpath)
    {
        return $xpath->query('//img[not(@alt) or @alt=""]')->length;
    }
    
    private function detectServerTechnology($headers)
    {
        $server = $headers['Server'][0] ?? 'غير محدد';
        $powered = $headers['X-Powered-By'][0] ?? null;
        
        return [
            'server' => $server,
            'powered_by' => $powered,
            'technology_stack' => $this->identifyTechStack($server, $powered)
        ];
    }
    
    private function identifyTechStack($server, $powered)
    {
        $stack = [];
        
        if (stripos($server, 'nginx') !== false) $stack[] = 'Nginx';
        if (stripos($server, 'apache') !== false) $stack[] = 'Apache';
        if (stripos($powered, 'php') !== false) $stack[] = 'PHP';
        if (stripos($powered, 'asp.net') !== false) $stack[] = 'ASP.NET';
        
        return $stack;
    }
    
    private function detectCMS($html)
    {
        $cms = [];
        
        if (strpos($html, 'wp-content') !== false || strpos($html, 'wordpress') !== false) {
            $cms[] = 'WordPress';
        }
        if (strpos($html, 'joomla') !== false) {
            $cms[] = 'Joomla';
        }
        if (strpos($html, 'drupal') !== false) {
            $cms[] = 'Drupal';
        }
        
        return $cms;
    }
    
    private function detectFrameworks($html)
    {
        $frameworks = [];
        
        if (strpos($html, 'bootstrap') !== false) $frameworks[] = 'Bootstrap';
        if (strpos($html, 'foundation') !== false) $frameworks[] = 'Foundation';
        if (strpos($html, 'tailwind') !== false) $frameworks[] = 'Tailwind CSS';
        
        return $frameworks;
    }
    
    private function detectJSLibraries($html)
    {
        $libraries = [];
        
        if (strpos($html, 'jquery') !== false) $libraries[] = 'jQuery';
        if (strpos($html, 'react') !== false) $libraries[] = 'React';
        if (strpos($html, 'vue') !== false) $libraries[] = 'Vue.js';
        if (strpos($html, 'angular') !== false) $libraries[] = 'Angular';
        
        return $libraries;
    }
    
    private function detectCSSFrameworks($html)
    {
        $frameworks = [];
        
        if (strpos($html, 'bootstrap') !== false) $frameworks[] = 'Bootstrap';
        if (strpos($html, 'bulma') !== false) $frameworks[] = 'Bulma';
        if (strpos($html, 'materialize') !== false) $frameworks[] = 'Materialize';
        
        return $frameworks;
    }
    
    private function detectAnalyticsTools($html)
    {
        $tools = [];
        
        if (strpos($html, 'google-analytics') !== false || strpos($html, 'gtag') !== false) {
            $tools[] = 'Google Analytics';
        }
        if (strpos($html, 'facebook.net') !== false) {
            $tools[] = 'Facebook Pixel';
        }
        
        return $tools;
    }
    
    private function analyzeCachingHeaders($headers)
    {
        return [
            'cache_control' => $headers['Cache-Control'][0] ?? 'غير محدد',
            'expires' => $headers['Expires'][0] ?? 'غير محدد',
            'etag' => $headers['ETag'][0] ?? 'غير محدد'
        ];
    }
    
    private function analyzeTitleTag($xpath)
    {
        $title = $xpath->query('//title')->item(0);
        if (!$title) {
            return [
                'exists' => false,
                'length' => 0,
                'recommendation' => 'أضف عنوان للصفحة'
            ];
        }
        
        $titleText = trim($title->textContent);
        $length = strlen($titleText);
        
        return [
            'exists' => true,
            'text' => $titleText,
            'length' => $length,
            'is_optimal' => $length >= 30 && $length <= 60,
            'recommendation' => $length < 30 ? 'العنوان قصير جداً' : ($length > 60 ? 'العنوان طويل جداً' : 'العنوان بطول مناسب')
        ];
    }
    
    private function analyzeMetaDescription($xpath)
    {
        $meta = $xpath->query('//meta[@name="description"]')->item(0);
        if (!$meta) {
            return [
                'exists' => false,
                'recommendation' => 'أضف وصف meta للصفحة'
            ];
        }
        
        $content = trim($meta->getAttribute('content'));
        $length = strlen($content);
        
        return [
            'exists' => true,
            'content' => $content,
            'length' => $length,
            'is_optimal' => $length >= 120 && $length <= 160,
            'recommendation' => $length < 120 ? 'الوصف قصير جداً' : ($length > 160 ? 'الوصف طويل جداً' : 'الوصف بطول مناسب')
        ];
    }
    
    private function analyzeHeadingStructure($xpath)
    {
        $headings = [];
        for ($i = 1; $i <= 6; $i++) {
            $count = $xpath->query("//h{$i}")->length;
            $headings["h{$i}"] = $count;
        }
        
        return [
            'structure' => $headings,
            'has_h1' => $headings['h1'] > 0,
            'h1_count' => $headings['h1'],
            'is_hierarchical' => $this->checkHeadingHierarchy($headings)
        ];
    }
    
    private function checkHeadingHierarchy($headings)
    {
        // فحص بسيط للتسلسل الهرمي
        return $headings['h1'] === 1 && $headings['h2'] > 0;
    }
    
    private function calculateTextLength($xpath)
    {
        $body = $xpath->query('//body')->item(0);
        if (!$body) return 0;
        
        $text = strip_tags($body->textContent);
        return strlen(trim($text));
    }
    
    private function analyzeKeywordDensity($xpath)
    {
        $body = $xpath->query('//body')->item(0);
        if (!$body) return [];
        
        $text = strtolower(strip_tags($body->textContent));
        $words = str_word_count($text, 1);
        $wordCount = count($words);
        
        if ($wordCount === 0) return [];
        
        $frequency = array_count_values($words);
        arsort($frequency);
        
        $top5 = array_slice($frequency, 0, 5, true);
        $density = [];
        
        foreach ($top5 as $word => $count) {
            if (strlen($word) > 3) { // تجاهل الكلمات القصيرة
                $density[$word] = round(($count / $wordCount) * 100, 2);
            }
        }
        
        return $density;
    }
    
    private function calculateReadabilityScore($xpath)
    {
        $body = $xpath->query('//body')->item(0);
        if (!$body) return 0;
        
        $text = strip_tags($body->textContent);
        $sentences = preg_split('/[.!?]+/', $text);
        $words = str_word_count($text);
        
        if (count($sentences) === 0) return 0;
        
        $avgWordsPerSentence = $words / count($sentences);
        
        // نقاط بناءً على متوسط الكلمات في الجملة
        if ($avgWordsPerSentence <= 15) return 85; // سهل القراءة
        if ($avgWordsPerSentence <= 20) return 70; // متوسط
        if ($avgWordsPerSentence <= 25) return 50; // صعب نوعاً ما
        return 30; // صعب القراءة
    }
    
    private function checkFileExists($url)
    {
        try {
            $response = $this->client->head($url);
            return [
                'exists' => $response->getStatusCode() === 200,
                'status_code' => $response->getStatusCode()
            ];
        } catch (\Exception $e) {
            return [
                'exists' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}