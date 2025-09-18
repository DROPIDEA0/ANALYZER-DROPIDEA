<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class WappalyzerService
{
    protected $client;
    
    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 60,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ]
        ]);
    }
    
    /**
     * تحليل التقنيات المستخدمة في الموقع
     */
    public function analyzeWebsiteTechnologies($url)
    {
        // محاولة استخدام Wappalyzer CLI أولاً
        $cliResult = $this->runWappalyzerCLI($url);
        
        if ($cliResult['success']) {
            return $cliResult;
        }
        
        // إذا فشل CLI، استخدم التحليل اليدوي
        return $this->manualTechnologyDetection($url);
    }
    
    /**
     * استخدام Wappalyzer CLI
     */
    protected function runWappalyzerCLI($url)
    {
        try {
            // تشغيل Wappalyzer CLI
            $result = Process::run([
                'npx', 'wappalyzer', $url, '--output', 'json', '--pretty'
            ], timeout: 30);
            
            if ($result->successful()) {
                $output = $result->output();
                $technologies = json_decode($output, true);
                
                return [
                    'success' => true,
                    'method' => 'cli',
                    'technologies' => $this->formatWappalyzerData($technologies)
                ];
            }
            
            Log::warning('Wappalyzer CLI failed', [
                'url' => $url,
                'error' => $result->errorOutput()
            ]);
            
            return ['success' => false, 'error' => 'CLI execution failed'];
            
        } catch (\Exception $e) {
            Log::error('Wappalyzer CLI error', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * الكشف اليدوي عن التقنيات
     */
    protected function manualTechnologyDetection($url)
    {
        try {
            $response = $this->client->get($url, [
                'allow_redirects' => true
            ]);
            
            $html = $response->getBody()->getContents();
            $headers = $response->getHeaders();
            
            $technologies = [
                'frontend' => $this->detectFrontendTechnologies($html),
                'backend' => $this->detectBackendTechnologies($headers, $html),
                'cms' => $this->detectCMS($html),
                'analytics' => $this->detectAnalytics($html),
                'javascript_frameworks' => $this->detectJavaScriptFrameworks($html),
                'css_frameworks' => $this->detectCSSFrameworks($html),
                'web_servers' => $this->detectWebServer($headers),
                'cdn' => $this->detectCDN($headers, $html),
                'security' => $this->detectSecurityTechnologies($headers, $html)
            ];
            
            return [
                'success' => true,
                'method' => 'manual',
                'technologies' => array_filter($technologies)
            ];
            
        } catch (GuzzleException $e) {
            Log::error('Manual technology detection error', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'فشل في تحليل التقنيات: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * كشف تقنيات الواجهة الأمامية
     */
    protected function detectFrontendTechnologies($html)
    {
        $frontend = [];
        
        // React
        if (preg_match('/react|__REACT_DEVTOOLS_GLOBAL_HOOK__|data-reactroot/i', $html)) {
            $frontend[] = ['name' => 'React', 'version' => null, 'category' => 'JavaScript Framework'];
        }
        
        // Vue.js
        if (preg_match('/vue\.js|__VUE__|v-if|v-for|@click/i', $html)) {
            $frontend[] = ['name' => 'Vue.js', 'version' => null, 'category' => 'JavaScript Framework'];
        }
        
        // Angular
        if (preg_match('/angular|ng-app|ng-controller|\[ng\w+\]|angular\.min\.js/i', $html)) {
            $frontend[] = ['name' => 'Angular', 'version' => null, 'category' => 'JavaScript Framework'];
        }
        
        // jQuery
        if (preg_match('/jquery|jQuery|\$\(|\$\./i', $html)) {
            $frontend[] = ['name' => 'jQuery', 'version' => null, 'category' => 'JavaScript Library'];
        }
        
        // Bootstrap
        if (preg_match('/bootstrap|btn-primary|container-fluid|col-md-|row/i', $html)) {
            $frontend[] = ['name' => 'Bootstrap', 'version' => null, 'category' => 'CSS Framework'];
        }
        
        // Tailwind CSS
        if (preg_match('/tailwind|tw-|bg-blue-|text-center|flex/i', $html)) {
            $frontend[] = ['name' => 'Tailwind CSS', 'version' => null, 'category' => 'CSS Framework'];
        }
        
        return $frontend;
    }
    
    /**
     * كشف تقنيات الخلفية
     */
    protected function detectBackendTechnologies($headers, $html)
    {
        $backend = [];
        
        // Server headers
        $serverHeader = $headers['server'][0] ?? $headers['Server'][0] ?? '';
        
        if (stripos($serverHeader, 'apache') !== false) {
            $backend[] = ['name' => 'Apache', 'version' => null, 'category' => 'Web Server'];
        }
        
        if (stripos($serverHeader, 'nginx') !== false) {
            $backend[] = ['name' => 'Nginx', 'version' => null, 'category' => 'Web Server'];
        }
        
        // PHP
        $xPoweredBy = $headers['x-powered-by'][0] ?? $headers['X-Powered-By'][0] ?? '';
        if (stripos($xPoweredBy, 'php') !== false) {
            preg_match('/PHP\/(\d+\.\d+\.\d+)/', $xPoweredBy, $matches);
            $backend[] = [
                'name' => 'PHP', 
                'version' => $matches[1] ?? null, 
                'category' => 'Programming Language'
            ];
        }
        
        // ASP.NET
        if (stripos($xPoweredBy, 'ASP.NET') !== false) {
            $backend[] = ['name' => 'ASP.NET', 'version' => null, 'category' => 'Framework'];
        }
        
        return $backend;
    }
    
    /**
     * كشف أنظمة إدارة المحتوى
     */
    protected function detectCMS($html)
    {
        $cms = [];
        
        // WordPress
        if (preg_match('/wp-content|wp-includes|wordpress|wp_head|wp-json/i', $html)) {
            $cms[] = ['name' => 'WordPress', 'version' => null, 'category' => 'CMS'];
        }
        
        // Drupal
        if (preg_match('/drupal|sites\/all\/|sites\/default\/|Drupal\.settings/i', $html)) {
            $cms[] = ['name' => 'Drupal', 'version' => null, 'category' => 'CMS'];
        }
        
        // Joomla
        if (preg_match('/joomla|com_content|option=com_|administrator\/index\.php/i', $html)) {
            $cms[] = ['name' => 'Joomla', 'version' => null, 'category' => 'CMS'];
        }
        
        // Shopify
        if (preg_match('/shopify|\.myshopify\.com|Shopify\.theme|cdn\.shopify\.com/i', $html)) {
            $cms[] = ['name' => 'Shopify', 'version' => null, 'category' => 'E-commerce'];
        }
        
        return $cms;
    }
    
    /**
     * كشف أدوات التحليلات
     */
    protected function detectAnalytics($html)
    {
        $analytics = [];
        
        // Google Analytics
        if (preg_match('/google-analytics|gtag\(|ga\(\'create\'|UA-\d+-\d+|G-[A-Z0-9]+/i', $html)) {
            $analytics[] = ['name' => 'Google Analytics', 'version' => null, 'category' => 'Analytics'];
        }
        
        // Facebook Pixel
        if (preg_match('/facebook\.net\/en_US\/fbevents\.js|fbq\(\'init\'|facebook pixel/i', $html)) {
            $analytics[] = ['name' => 'Facebook Pixel', 'version' => null, 'category' => 'Analytics'];
        }
        
        // Google Tag Manager
        if (preg_match('/googletagmanager\.com\/gtm\.js|GTM-[A-Z0-9]+/i', $html)) {
            $analytics[] = ['name' => 'Google Tag Manager', 'version' => null, 'category' => 'Tag Manager'];
        }
        
        return $analytics;
    }
    
    /**
     * كشف إطارات عمل JavaScript
     */
    protected function detectJavaScriptFrameworks($html)
    {
        $frameworks = [];
        
        // Next.js
        if (preg_match('/_next\/|__NEXT_DATA__|next\.js/i', $html)) {
            $frameworks[] = ['name' => 'Next.js', 'version' => null, 'category' => 'JavaScript Framework'];
        }
        
        // Nuxt.js
        if (preg_match('/_nuxt\/|__NUXT__|nuxt\.js/i', $html)) {
            $frameworks[] = ['name' => 'Nuxt.js', 'version' => null, 'category' => 'JavaScript Framework'];
        }
        
        // Gatsby
        if (preg_match('/gatsby|___gatsby/i', $html)) {
            $frameworks[] = ['name' => 'Gatsby', 'version' => null, 'category' => 'Static Site Generator'];
        }
        
        return $frameworks;
    }
    
    /**
     * كشف إطارات عمل CSS
     */
    protected function detectCSSFrameworks($html)
    {
        $frameworks = [];
        
        // Foundation
        if (preg_match('/foundation|zurb/i', $html)) {
            $frameworks[] = ['name' => 'Foundation', 'version' => null, 'category' => 'CSS Framework'];
        }
        
        // Bulma
        if (preg_match('/bulma|is-primary|has-text-/i', $html)) {
            $frameworks[] = ['name' => 'Bulma', 'version' => null, 'category' => 'CSS Framework'];
        }
        
        return $frameworks;
    }
    
    /**
     * كشف خادم الويب
     */
    protected function detectWebServer($headers)
    {
        $servers = [];
        
        $serverHeader = $headers['server'][0] ?? $headers['Server'][0] ?? '';
        
        if ($serverHeader) {
            $servers[] = ['name' => $serverHeader, 'version' => null, 'category' => 'Web Server'];
        }
        
        return $servers;
    }
    
    /**
     * كشف CDN
     */
    protected function detectCDN($headers, $html)
    {
        $cdns = [];
        
        // CloudFlare
        $cfRay = $headers['cf-ray'][0] ?? $headers['CF-RAY'][0] ?? '';
        if ($cfRay || stripos($html, 'cloudflare') !== false) {
            $cdns[] = ['name' => 'Cloudflare', 'version' => null, 'category' => 'CDN'];
        }
        
        // AWS CloudFront
        $cfId = $headers['x-amz-cf-id'][0] ?? $headers['X-Amz-Cf-Id'][0] ?? '';
        if ($cfId) {
            $cdns[] = ['name' => 'Amazon CloudFront', 'version' => null, 'category' => 'CDN'];
        }
        
        return $cdns;
    }
    
    /**
     * كشف تقنيات الأمان
     */
    protected function detectSecurityTechnologies($headers, $html)
    {
        $security = [];
        
        // Security headers
        if (isset($headers['strict-transport-security']) || isset($headers['Strict-Transport-Security'])) {
            $security[] = ['name' => 'HSTS', 'version' => null, 'category' => 'Security'];
        }
        
        if (isset($headers['content-security-policy']) || isset($headers['Content-Security-Policy'])) {
            $security[] = ['name' => 'CSP', 'version' => null, 'category' => 'Security'];
        }
        
        return $security;
    }
    
    /**
     * تنسيق بيانات Wappalyzer
     */
    protected function formatWappalyzerData($data)
    {
        if (!is_array($data)) return [];
        
        $formatted = [
            'frontend' => [],
            'backend' => [],
            'cms' => [],
            'analytics' => [],
            'javascript_frameworks' => [],
            'css_frameworks' => [],
            'web_servers' => [],
            'cdn' => [],
            'security' => []
        ];
        
        foreach ($data as $tech) {
            $category = strtolower($tech['category'] ?? 'other');
            $techInfo = [
                'name' => $tech['name'] ?? 'Unknown',
                'version' => $tech['version'] ?? null,
                'category' => $tech['category'] ?? 'Other',
                'confidence' => $tech['confidence'] ?? 100
            ];
            
            if (in_array($category, ['javascript frameworks', 'ui frameworks'])) {
                $formatted['javascript_frameworks'][] = $techInfo;
            } elseif (in_array($category, ['cms', 'blogs'])) {
                $formatted['cms'][] = $techInfo;
            } elseif (in_array($category, ['analytics', 'advertising'])) {
                $formatted['analytics'][] = $techInfo;
            } elseif (in_array($category, ['web servers'])) {
                $formatted['web_servers'][] = $techInfo;
            } elseif (in_array($category, ['cdn'])) {
                $formatted['cdn'][] = $techInfo;
            } else {
                $formatted['frontend'][] = $techInfo;
            }
        }
        
        return array_filter($formatted);
    }
}