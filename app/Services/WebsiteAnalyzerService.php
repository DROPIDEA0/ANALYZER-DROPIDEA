<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use DOMDocument;
use DOMXPath;

class WebsiteAnalyzerService
{
    /**
     * تحليل أساسي للموقع
     */
    public function analyzeWebsite($url)
    {
        try {
            // جلب محتوى الصفحة
            $response = Http::timeout(30)->get($url);
            
            if (!$response->successful()) {
                throw new \Exception('لا يمكن الوصول إلى الموقع');
            }

            $html = $response->body();
            $headers = $response->headers();

            // تحليل HTML
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);

            return [
                'url' => $url,
                'status_code' => $response->status(),
                'response_time' => $this->calculateResponseTime($url),
                'title' => $this->extractTitle($xpath),
                'meta_description' => $this->extractMetaDescription($xpath),
                'meta_keywords' => $this->extractMetaKeywords($xpath),
                'h1_tags' => $this->extractH1Tags($xpath),
                'h2_tags' => $this->extractH2Tags($xpath),
                'images_count' => $this->countImages($xpath),
                'links_count' => $this->countLinks($xpath),
                'word_count' => $this->countWords($html),
                'has_ssl' => $this->checkSSL($url),
                'server_info' => $headers['server'][0] ?? 'غير محدد',
                'content_type' => $headers['content-type'][0] ?? 'غير محدد',
                'content_length' => strlen($html),
                'language' => $this->detectLanguage($xpath),
                'charset' => $this->detectCharset($xpath),
                'viewport' => $this->checkViewport($xpath),
                'robots_meta' => $this->extractRobotsMeta($xpath),
            ];

        } catch (\Exception $e) {
            throw new \Exception('خطأ في تحليل الموقع: ' . $e->getMessage());
        }
    }

    /**
     * حساب وقت الاستجابة
     */
    private function calculateResponseTime($url)
    {
        $start = microtime(true);
        
        try {
            Http::timeout(10)->get($url);
            $end = microtime(true);
            return round(($end - $start) * 1000, 2); // بالميلي ثانية
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * استخراج العنوان
     */
    private function extractTitle($xpath)
    {
        $titleNodes = $xpath->query('//title');
        return $titleNodes->length > 0 ? trim($titleNodes->item(0)->textContent) : null;
    }

    /**
     * استخراج الوصف التعريفي
     */
    private function extractMetaDescription($xpath)
    {
        $metaNodes = $xpath->query('//meta[@name="description"]/@content');
        return $metaNodes->length > 0 ? trim($metaNodes->item(0)->textContent) : null;
    }

    /**
     * استخراج الكلمات المفتاحية
     */
    private function extractMetaKeywords($xpath)
    {
        $metaNodes = $xpath->query('//meta[@name="keywords"]/@content');
        return $metaNodes->length > 0 ? trim($metaNodes->item(0)->textContent) : null;
    }

    /**
     * استخراج عناوين H1
     */
    private function extractH1Tags($xpath)
    {
        $h1Nodes = $xpath->query('//h1');
        $h1Tags = [];
        
        foreach ($h1Nodes as $node) {
            $h1Tags[] = trim($node->textContent);
        }
        
        return $h1Tags;
    }

    /**
     * استخراج عناوين H2
     */
    private function extractH2Tags($xpath)
    {
        $h2Nodes = $xpath->query('//h2');
        $h2Tags = [];
        
        foreach ($h2Nodes as $node) {
            $h2Tags[] = trim($node->textContent);
        }
        
        return $h2Tags;
    }

    /**
     * عد الصور
     */
    private function countImages($xpath)
    {
        $imgNodes = $xpath->query('//img');
        return $imgNodes->length;
    }

    /**
     * عد الروابط
     */
    private function countLinks($xpath)
    {
        $linkNodes = $xpath->query('//a[@href]');
        return $linkNodes->length;
    }

    /**
     * عد الكلمات
     */
    private function countWords($html)
    {
        $text = strip_tags($html);
        $text = preg_replace('/\s+/', ' ', $text);
        $words = explode(' ', trim($text));
        return count(array_filter($words));
    }

    /**
     * فحص SSL
     */
    private function checkSSL($url)
    {
        return strpos($url, 'https://') === 0;
    }

    /**
     * اكتشاف اللغة
     */
    private function detectLanguage($xpath)
    {
        $langNodes = $xpath->query('//html/@lang');
        if ($langNodes->length > 0) {
            return $langNodes->item(0)->textContent;
        }
        
        $langNodes = $xpath->query('//meta[@http-equiv="content-language"]/@content');
        return $langNodes->length > 0 ? $langNodes->item(0)->textContent : null;
    }

    /**
     * اكتشاف ترميز الأحرف
     */
    private function detectCharset($xpath)
    {
        $charsetNodes = $xpath->query('//meta[@charset]/@charset');
        if ($charsetNodes->length > 0) {
            return $charsetNodes->item(0)->textContent;
        }
        
        $charsetNodes = $xpath->query('//meta[@http-equiv="content-type"]/@content');
        if ($charsetNodes->length > 0) {
            $content = $charsetNodes->item(0)->textContent;
            if (preg_match('/charset=([^;]+)/i', $content, $matches)) {
                return trim($matches[1]);
            }
        }
        
        return null;
    }

    /**
     * فحص viewport
     */
    private function checkViewport($xpath)
    {
        $viewportNodes = $xpath->query('//meta[@name="viewport"]/@content');
        return $viewportNodes->length > 0 ? $viewportNodes->item(0)->textContent : null;
    }

    /**
     * استخراج robots meta
     */
    private function extractRobotsMeta($xpath)
    {
        $robotsNodes = $xpath->query('//meta[@name="robots"]/@content');
        return $robotsNodes->length > 0 ? $robotsNodes->item(0)->textContent : null;
    }
}
