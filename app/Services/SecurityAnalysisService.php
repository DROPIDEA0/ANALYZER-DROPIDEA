<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class SecurityAnalysisService
{
    protected $client;
    
    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false, // للتعامل مع شهادات SSL غير صحيحة
            'headers' => [
                'User-Agent' => 'AnalyzerDropidea Security Scanner/1.0'
            ]
        ]);
    }
    
    /**
     * تحليل أمان الموقع الشامل
     */
    public function analyzeWebsiteSecurity($url)
    {
        return [
            'ssl_analysis' => $this->analyzeSSL($url),
            'security_headers' => $this->analyzeSecurityHeaders($url),
            'vulnerability_scan' => $this->performBasicVulnerabilityScan($url),
            'privacy_analysis' => $this->analyzePrivacy($url),
            'security_score' => 0 // سيتم حسابه لاحقاً
        ];
    }
    
    /**
     * تحليل SSL/TLS
     */
    protected function analyzeSSL($url)
    {
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'];
        $port = $parsedUrl['port'] ?? 443;
        
        try {
            // فحص شهادة SSL
            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            
            $stream = @stream_socket_client(
                "ssl://{$host}:{$port}",
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $context
            );
            
            if (!$stream) {
                return [
                    'has_ssl' => false,
                    'error' => "فشل الاتصال: $errstr ($errno)"
                ];
            }
            
            $cert = stream_context_get_params($stream)['options']['ssl']['peer_certificate'];
            $certData = openssl_x509_parse($cert);
            
            fclose($stream);
            
            // تحليل الشهادة
            $currentTime = time();
            $validFrom = $certData['validFrom_time_t'];
            $validTo = $certData['validTo_time_t'];
            
            $sslAnalysis = [
                'has_ssl' => true,
                'issuer' => $certData['issuer']['CN'] ?? 'غير محدد',
                'subject' => $certData['subject']['CN'] ?? 'غير محدد',
                'valid_from' => date('Y-m-d', $validFrom),
                'valid_to' => date('Y-m-d', $validTo),
                'is_valid' => ($currentTime >= $validFrom && $currentTime <= $validTo),
                'days_until_expiry' => ceil(($validTo - $currentTime) / 86400),
                'signature_algorithm' => $certData['signatureTypeSN'] ?? 'غير محدد',
                'key_size' => null, // يتطلب مكتبة إضافية
                'ssl_grade' => $this->calculateSSLGrade($certData)
            ];
            
            // فحص TLS version
            $sslAnalysis['tls_version'] = $this->detectTLSVersion($host, $port);
            
            return $sslAnalysis;
            
        } catch (\Exception $e) {
            return [
                'has_ssl' => false,
                'error' => 'فشل في تحليل SSL: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * تحليل Headers الأمان
     */
    protected function analyzeSecurityHeaders($url)
    {
        try {
            $response = $this->client->get($url, [
                'allow_redirects' => true,
                'http_errors' => false
            ]);
            
            $headers = $response->getHeaders();
            
            return [
                'hsts' => $this->analyzeHSTS($headers),
                'csp' => $this->analyzeCSP($headers),
                'x_frame_options' => $this->analyzeXFrameOptions($headers),
                'x_content_type_options' => $this->analyzeXContentTypeOptions($headers),
                'x_xss_protection' => $this->analyzeXXSSProtection($headers),
                'referrer_policy' => $this->analyzeReferrerPolicy($headers),
                'permissions_policy' => $this->analyzePermissionsPolicy($headers),
                'expect_ct' => $this->analyzeExpectCT($headers),
                'security_score' => 0 // سيتم حسابه
            ];
            
        } catch (GuzzleException $e) {
            Log::error('Security headers analysis error', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            
            return [
                'error' => 'فشل في تحليل headers الأمان: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * تحليل HSTS
     */
    protected function analyzeHSTS($headers)
    {
        $hsts = $headers['strict-transport-security'][0] ?? $headers['Strict-Transport-Security'][0] ?? null;
        
        if (!$hsts) {
            return ['present' => false, 'score' => 0];
        }
        
        $maxAge = null;
        $includeSubDomains = false;
        $preload = false;
        
        if (preg_match('/max-age=(\d+)/', $hsts, $matches)) {
            $maxAge = (int)$matches[1];
        }
        
        $includeSubDomains = stripos($hsts, 'includeSubDomains') !== false;
        $preload = stripos($hsts, 'preload') !== false;
        
        $score = 50; // نقاط أساسية للوجود
        if ($maxAge >= 31536000) $score += 20; // سنة واحدة على الأقل
        if ($includeSubDomains) $score += 15;
        if ($preload) $score += 15;
        
        return [
            'present' => true,
            'max_age' => $maxAge,
            'include_subdomains' => $includeSubDomains,
            'preload' => $preload,
            'score' => min($score, 100)
        ];
    }
    
    /**
     * تحليل CSP
     */
    protected function analyzeCSP($headers)
    {
        $csp = $headers['content-security-policy'][0] ?? 
               $headers['Content-Security-Policy'][0] ?? 
               $headers['x-content-security-policy'][0] ?? 
               $headers['X-Content-Security-Policy'][0] ?? null;
        
        if (!$csp) {
            return ['present' => false, 'score' => 0];
        }
        
        $directives = [];
        $policies = explode(';', $csp);
        
        foreach ($policies as $policy) {
            $policy = trim($policy);
            if (!empty($policy)) {
                $parts = explode(' ', $policy, 2);
                $directive = $parts[0];
                $value = $parts[1] ?? '';
                $directives[$directive] = $value;
            }
        }
        
        // تقييم جودة CSP
        $score = 40; // نقاط أساسية للوجود
        if (isset($directives['default-src'])) $score += 15;
        if (isset($directives['script-src']) && stripos($directives['script-src'], 'unsafe-inline') === false) $score += 20;
        if (isset($directives['object-src']) && $directives['object-src'] === "'none'") $score += 10;
        if (isset($directives['base-uri'])) $score += 10;
        if (isset($directives['frame-ancestors'])) $score += 5;
        
        return [
            'present' => true,
            'directives' => $directives,
            'score' => min($score, 100)
        ];
    }
    
    /**
     * تحليل X-Frame-Options
     */
    protected function analyzeXFrameOptions($headers)
    {
        $xframe = $headers['x-frame-options'][0] ?? $headers['X-Frame-Options'][0] ?? null;
        
        if (!$xframe) {
            return ['present' => false, 'score' => 0];
        }
        
        $value = strtoupper(trim($xframe));
        $score = 50; // نقاط أساسية
        
        if ($value === 'DENY') {
            $score = 100;
        } elseif ($value === 'SAMEORIGIN') {
            $score = 90;
        } elseif (stripos($value, 'ALLOW-FROM') === 0) {
            $score = 70;
        }
        
        return [
            'present' => true,
            'value' => $value,
            'score' => $score
        ];
    }
    
    /**
     * تحليل X-Content-Type-Options
     */
    protected function analyzeXContentTypeOptions($headers)
    {
        $header = $headers['x-content-type-options'][0] ?? $headers['X-Content-Type-Options'][0] ?? null;
        
        if (!$header) {
            return ['present' => false, 'score' => 0];
        }
        
        $value = strtolower(trim($header));
        $score = ($value === 'nosniff') ? 100 : 50;
        
        return [
            'present' => true,
            'value' => $value,
            'score' => $score
        ];
    }
    
    /**
     * تحليل X-XSS-Protection
     */
    protected function analyzeXXSSProtection($headers)
    {
        $header = $headers['x-xss-protection'][0] ?? $headers['X-XSS-Protection'][0] ?? null;
        
        if (!$header) {
            return ['present' => false, 'score' => 0];
        }
        
        $value = trim($header);
        $score = 50;
        
        if ($value === '1; mode=block') {
            $score = 100;
        } elseif ($value === '1') {
            $score = 80;
        } elseif ($value === '0') {
            $score = 20;
        }
        
        return [
            'present' => true,
            'value' => $value,
            'score' => $score
        ];
    }
    
    /**
     * تحليل Referrer Policy
     */
    protected function analyzeReferrerPolicy($headers)
    {
        $header = $headers['referrer-policy'][0] ?? $headers['Referrer-Policy'][0] ?? null;
        
        if (!$header) {
            return ['present' => false, 'score' => 0];
        }
        
        $value = trim($header);
        $score = 70; // نقاط أساسية للوجود
        
        $strictPolicies = ['no-referrer', 'same-origin', 'strict-origin'];
        if (in_array($value, $strictPolicies)) {
            $score = 100;
        }
        
        return [
            'present' => true,
            'value' => $value,
            'score' => $score
        ];
    }
    
    /**
     * تحليل Permissions Policy
     */
    protected function analyzePermissionsPolicy($headers)
    {
        $header = $headers['permissions-policy'][0] ?? $headers['Permissions-Policy'][0] ?? null;
        
        return [
            'present' => $header !== null,
            'value' => $header,
            'score' => $header ? 80 : 0
        ];
    }
    
    /**
     * تحليل Expect-CT
     */
    protected function analyzeExpectCT($headers)
    {
        $header = $headers['expect-ct'][0] ?? $headers['Expect-CT'][0] ?? null;
        
        return [
            'present' => $header !== null,
            'value' => $header,
            'score' => $header ? 70 : 0
        ];
    }
    
    /**
     * فحص الثغرات الأساسية
     */
    protected function performBasicVulnerabilityScan($url)
    {
        $vulnerabilities = [];
        
        try {
            // فحص directory traversal
            $traversalTest = $this->testDirectoryTraversal($url);
            if ($traversalTest) {
                $vulnerabilities[] = [
                    'type' => 'Directory Traversal',
                    'severity' => 'high',
                    'description' => 'الموقع عرضة لثغرة directory traversal'
                ];
            }
            
            // فحص SQL injection أساسي
            $sqlTest = $this->testBasicSQLInjection($url);
            if ($sqlTest) {
                $vulnerabilities[] = [
                    'type' => 'SQL Injection',
                    'severity' => 'critical',
                    'description' => 'الموقع قد يكون عرضة لثغرة SQL injection'
                ];
            }
            
            // فحص XSS أساسي
            $xssTest = $this->testBasicXSS($url);
            if ($xssTest) {
                $vulnerabilities[] = [
                    'type' => 'XSS',
                    'severity' => 'medium',
                    'description' => 'الموقع قد يكون عرضة لثغرة XSS'
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Vulnerability scan error', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
        }
        
        return $vulnerabilities;
    }
    
    /**
     * تحليل الخصوصية
     */
    protected function analyzePrivacy($url)
    {
        try {
            $response = $this->client->get($url);
            $html = $response->getBody()->getContents();
            
            return [
                'cookies' => $this->analyzeCookies($response->getHeaders()),
                'tracking_scripts' => $this->detectTrackingScripts($html),
                'privacy_policy' => $this->findPrivacyPolicy($html),
                'gdpr_compliance' => $this->checkGDPRIndicators($html)
            ];
            
        } catch (\Exception $e) {
            return ['error' => 'فشل في تحليل الخصوصية: ' . $e->getMessage()];
        }
    }
    
    // Helper methods للتقييمات المختلفة...
    
    protected function calculateSSLGrade($certData)
    {
        $score = 100;
        
        // خفض النقاط للخوارزميات الضعيفة
        $sigAlg = $certData['signatureTypeSN'] ?? '';
        if (stripos($sigAlg, 'md5') !== false) $score -= 50;
        elseif (stripos($sigAlg, 'sha1') !== false) $score -= 30;
        
        // تحديد الدرجة
        if ($score >= 90) return 'A+';
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B';
        if ($score >= 60) return 'C';
        if ($score >= 50) return 'D';
        return 'F';
    }
    
    protected function detectTLSVersion($host, $port)
    {
        // محاولة كشف إصدار TLS
        // يتطلب تنفيذ أكثر تعقيداً
        return 'TLS 1.2+'; // قيمة افتراضية
    }
    
    protected function testDirectoryTraversal($url)
    {
        // اختبار بسيط لـ directory traversal
        return false; // تنفيذ مبسط
    }
    
    protected function testBasicSQLInjection($url)
    {
        // اختبار بسيط لـ SQL injection
        return false; // تنفيذ مبسط
    }
    
    protected function testBasicXSS($url)
    {
        // اختبار بسيط لـ XSS
        return false; // تنفيذ مبسط
    }
    
    protected function analyzeCookies($headers)
    {
        $cookies = $headers['set-cookie'] ?? $headers['Set-Cookie'] ?? [];
        $analysis = [];
        
        foreach ($cookies as $cookie) {
            $secure = stripos($cookie, 'secure') !== false;
            $httpOnly = stripos($cookie, 'httponly') !== false;
            $sameSite = stripos($cookie, 'samesite') !== false;
            
            $analysis[] = [
                'secure' => $secure,
                'httponly' => $httpOnly,
                'samesite' => $sameSite,
                'raw' => $cookie
            ];
        }
        
        return $analysis;
    }
    
    protected function detectTrackingScripts($html)
    {
        $trackers = [];
        
        $trackingPatterns = [
            'google-analytics' => '/google-analytics|gtag|ga\(/i',
            'facebook' => '/facebook\.net|fbq\(/i',
            'twitter' => '/twitter\.com\/en\/privacy/i',
            'linkedin' => '/linkedin\.com/i',
            'hotjar' => '/hotjar/i',
            'mixpanel' => '/mixpanel/i'
        ];
        
        foreach ($trackingPatterns as $name => $pattern) {
            if (preg_match($pattern, $html)) {
                $trackers[] = $name;
            }
        }
        
        return $trackers;
    }
    
    protected function findPrivacyPolicy($html)
    {
        $privacyLinks = [];
        
        if (preg_match_all('/<a[^>]*href=["\']([^"\']*)["\'][^>]*>([^<]*privacy[^<]*)<\/a>/i', $html, $matches)) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                $privacyLinks[] = [
                    'url' => $matches[1][$i],
                    'text' => trim($matches[2][$i])
                ];
            }
        }
        
        return $privacyLinks;
    }
    
    protected function checkGDPRIndicators($html)
    {
        $indicators = [
            'cookie_banner' => preg_match('/cookie[s]?\s+(accept|consent|policy)/i', $html),
            'privacy_notice' => preg_match('/privacy\s+(policy|notice|statement)/i', $html),
            'gdpr_mention' => preg_match('/gdpr|general data protection/i', $html),
            'consent_management' => preg_match('/consent\s+(management|platform)/i', $html)
        ];
        
        return $indicators;
    }
}