<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use DOMDocument;
use DOMXPath;

class SeoAnalyzerService
{
    /**
     * تحليل السيو للموقع
     */
    public function analyzeSeo($url)
    {
        try {
            // جلب محتوى الصفحة
            $response = Http::timeout(30)->get($url);
            
            if (!$response->successful()) {
                throw new \Exception('لا يمكن الوصول إلى الموقع');
            }

            $html = $response->body();
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);

            // تحليل عناصر السيو المختلفة
            $seoData = [
                'title' => $this->analyzeTitle($xpath),
                'meta_description' => $this->analyzeMetaDescription($xpath),
                'headings' => $this->analyzeHeadings($xpath),
                'images' => $this->analyzeImages($xpath),
                'links' => $this->analyzeLinks($xpath, $url),
                'content' => $this->analyzeContent($html),
                'technical' => $this->analyzeTechnical($xpath, $url),
                'social_media' => $this->analyzeSocialMedia($xpath),
                'structured_data' => $this->analyzeStructuredData($html),
            ];

            // حساب النقاط الإجمالية
            $score = $this->calculateSeoScore($seoData);

            return [
                'score' => $score,
                'grade' => $this->getGrade($score),
                'details' => $seoData,
                'recommendations' => $this->generateRecommendations($seoData),
                'has_meta_description' => !empty($seoData['meta_description']['content']),
                'title_length' => $seoData['title']['length'],
                'meta_description_length' => $seoData['meta_description']['length'],
                'h1_count' => $seoData['headings']['h1_count'],
                'images_without_alt' => $seoData['images']['without_alt'],
                'internal_links' => $seoData['links']['internal_count'],
                'external_links' => $seoData['links']['external_count'],
            ];

        } catch (\Exception $e) {
            throw new \Exception('خطأ في تحليل السيو: ' . $e->getMessage());
        }
    }

    /**
     * تحليل العنوان
     */
    private function analyzeTitle($xpath)
    {
        $titleNodes = $xpath->query('//title');
        $title = $titleNodes->length > 0 ? trim($titleNodes->item(0)->textContent) : '';
        
        return [
            'content' => $title,
            'length' => mb_strlen($title),
            'is_optimal' => mb_strlen($title) >= 30 && mb_strlen($title) <= 60,
            'is_present' => !empty($title),
        ];
    }

    /**
     * تحليل الوصف التعريفي
     */
    private function analyzeMetaDescription($xpath)
    {
        $metaNodes = $xpath->query('//meta[@name="description"]/@content');
        $description = $metaNodes->length > 0 ? trim($metaNodes->item(0)->textContent) : '';
        
        return [
            'content' => $description,
            'length' => mb_strlen($description),
            'is_optimal' => mb_strlen($description) >= 120 && mb_strlen($description) <= 160,
            'is_present' => !empty($description),
        ];
    }

    /**
     * تحليل العناوين
     */
    private function analyzeHeadings($xpath)
    {
        $h1Nodes = $xpath->query('//h1');
        $h2Nodes = $xpath->query('//h2');
        $h3Nodes = $xpath->query('//h3');
        
        $h1Tags = [];
        foreach ($h1Nodes as $node) {
            $h1Tags[] = trim($node->textContent);
        }
        
        return [
            'h1_count' => $h1Nodes->length,
            'h2_count' => $h2Nodes->length,
            'h3_count' => $h3Nodes->length,
            'h1_tags' => $h1Tags,
            'has_single_h1' => $h1Nodes->length === 1,
            'has_hierarchy' => $h1Nodes->length > 0 && $h2Nodes->length > 0,
        ];
    }

    /**
     * تحليل الصور
     */
    private function analyzeImages($xpath)
    {
        $imgNodes = $xpath->query('//img');
        $totalImages = $imgNodes->length;
        $withoutAlt = 0;
        $withoutTitle = 0;
        
        foreach ($imgNodes as $img) {
            if (!$img->hasAttribute('alt') || empty(trim($img->getAttribute('alt')))) {
                $withoutAlt++;
            }
            if (!$img->hasAttribute('title') || empty(trim($img->getAttribute('title')))) {
                $withoutTitle++;
            }
        }
        
        return [
            'total_count' => $totalImages,
            'without_alt' => $withoutAlt,
            'without_title' => $withoutTitle,
            'alt_percentage' => $totalImages > 0 ? round((($totalImages - $withoutAlt) / $totalImages) * 100, 2) : 100,
        ];
    }

    /**
     * تحليل الروابط
     */
    private function analyzeLinks($xpath, $baseUrl)
    {
        $linkNodes = $xpath->query('//a[@href]');
        $internalLinks = 0;
        $externalLinks = 0;
        $brokenLinks = 0;
        
        $baseDomain = parse_url($baseUrl, PHP_URL_HOST);
        
        foreach ($linkNodes as $link) {
            $href = $link->getAttribute('href');
            
            if (empty($href) || $href === '#') {
                continue;
            }
            
            if (strpos($href, 'http') === 0) {
                $linkDomain = parse_url($href, PHP_URL_HOST);
                if ($linkDomain === $baseDomain) {
                    $internalLinks++;
                } else {
                    $externalLinks++;
                }
            } else {
                $internalLinks++;
            }
        }
        
        return [
            'total_count' => $linkNodes->length,
            'internal_count' => $internalLinks,
            'external_count' => $externalLinks,
            'broken_count' => $brokenLinks,
            'internal_external_ratio' => $externalLinks > 0 ? round($internalLinks / $externalLinks, 2) : $internalLinks,
        ];
    }

    /**
     * تحليل المحتوى
     */
    private function analyzeContent($html)
    {
        $text = strip_tags($html);
        $text = preg_replace('/\s+/', ' ', $text);
        $words = explode(' ', trim($text));
        $wordCount = count(array_filter($words));
        
        // تحليل كثافة الكلمات المفتاحية (مبسط)
        $wordFrequency = array_count_values(array_map('strtolower', $words));
        arsort($wordFrequency);
        $topWords = array_slice($wordFrequency, 0, 10, true);
        
        return [
            'word_count' => $wordCount,
            'character_count' => mb_strlen($text),
            'is_sufficient' => $wordCount >= 300,
            'top_keywords' => $topWords,
            'readability_score' => $this->calculateReadabilityScore($text),
        ];
    }

    /**
     * تحليل العناصر التقنية
     */
    private function analyzeTechnical($xpath, $url)
    {
        // فحص robots.txt
        $robotsTxt = $this->checkRobotsTxt($url);
        
        // فحص sitemap
        $sitemap = $this->checkSitemap($url);
        
        // فحص canonical
        $canonicalNodes = $xpath->query('//link[@rel="canonical"]/@href');
        $canonical = $canonicalNodes->length > 0 ? $canonicalNodes->item(0)->textContent : null;
        
        // فحص viewport
        $viewportNodes = $xpath->query('//meta[@name="viewport"]');
        $hasViewport = $viewportNodes->length > 0;
        
        // فحص SSL
        $hasSSL = strpos($url, 'https://') === 0;
        
        return [
            'has_robots_txt' => $robotsTxt,
            'has_sitemap' => $sitemap,
            'has_canonical' => !empty($canonical),
            'canonical_url' => $canonical,
            'has_viewport' => $hasViewport,
            'has_ssl' => $hasSSL,
            'is_mobile_friendly' => $hasViewport,
        ];
    }

    /**
     * تحليل وسائل التواصل الاجتماعي
     */
    private function analyzeSocialMedia($xpath)
    {
        // Open Graph tags
        $ogTitle = $xpath->query('//meta[@property="og:title"]/@content');
        $ogDescription = $xpath->query('//meta[@property="og:description"]/@content');
        $ogImage = $xpath->query('//meta[@property="og:image"]/@content');
        
        // Twitter Card tags
        $twitterCard = $xpath->query('//meta[@name="twitter:card"]/@content');
        $twitterTitle = $xpath->query('//meta[@name="twitter:title"]/@content');
        $twitterDescription = $xpath->query('//meta[@name="twitter:description"]/@content');
        
        return [
            'has_og_tags' => $ogTitle->length > 0 || $ogDescription->length > 0 || $ogImage->length > 0,
            'og_title' => $ogTitle->length > 0 ? $ogTitle->item(0)->textContent : null,
            'og_description' => $ogDescription->length > 0 ? $ogDescription->item(0)->textContent : null,
            'og_image' => $ogImage->length > 0 ? $ogImage->item(0)->textContent : null,
            'has_twitter_cards' => $twitterCard->length > 0,
            'twitter_card_type' => $twitterCard->length > 0 ? $twitterCard->item(0)->textContent : null,
        ];
    }

    /**
     * تحليل البيانات المنظمة
     */
    private function analyzeStructuredData($html)
    {
        $hasJsonLd = strpos($html, 'application/ld+json') !== false;
        $hasMicrodata = strpos($html, 'itemscope') !== false;
        $hasRdfa = strpos($html, 'typeof') !== false;
        
        return [
            'has_structured_data' => $hasJsonLd || $hasMicrodata || $hasRdfa,
            'has_json_ld' => $hasJsonLd,
            'has_microdata' => $hasMicrodata,
            'has_rdfa' => $hasRdfa,
        ];
    }

    /**
     * حساب نقاط السيو
     */
    private function calculateSeoScore($seoData)
    {
        $score = 0;
        $maxScore = 100;
        
        // العنوان (20 نقطة)
        if ($seoData['title']['is_present']) $score += 10;
        if ($seoData['title']['is_optimal']) $score += 10;
        
        // الوصف التعريفي (15 نقطة)
        if ($seoData['meta_description']['is_present']) $score += 8;
        if ($seoData['meta_description']['is_optimal']) $score += 7;
        
        // العناوين (15 نقطة)
        if ($seoData['headings']['has_single_h1']) $score += 8;
        if ($seoData['headings']['has_hierarchy']) $score += 7;
        
        // الصور (10 نقاط)
        if ($seoData['images']['alt_percentage'] >= 80) $score += 10;
        elseif ($seoData['images']['alt_percentage'] >= 50) $score += 5;
        
        // المحتوى (15 نقطة)
        if ($seoData['content']['is_sufficient']) $score += 10;
        if ($seoData['content']['readability_score'] >= 60) $score += 5;
        
        // العناصر التقنية (15 نقاط)
        if ($seoData['technical']['has_ssl']) $score += 5;
        if ($seoData['technical']['has_viewport']) $score += 3;
        if ($seoData['technical']['has_canonical']) $score += 3;
        if ($seoData['technical']['has_robots_txt']) $score += 2;
        if ($seoData['technical']['has_sitemap']) $score += 2;
        
        // وسائل التواصل الاجتماعي (5 نقاط)
        if ($seoData['social_media']['has_og_tags']) $score += 3;
        if ($seoData['social_media']['has_twitter_cards']) $score += 2;
        
        // البيانات المنظمة (5 نقاط)
        if ($seoData['structured_data']['has_structured_data']) $score += 5;
        
        return min($score, $maxScore);
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
    private function generateRecommendations($seoData)
    {
        $recommendations = [];
        
        if (!$seoData['title']['is_present']) {
            $recommendations[] = 'إضافة عنوان للصفحة';
        } elseif (!$seoData['title']['is_optimal']) {
            $recommendations[] = 'تحسين طول العنوان (30-60 حرف)';
        }
        
        if (!$seoData['meta_description']['is_present']) {
            $recommendations[] = 'إضافة وصف تعريفي للصفحة';
        } elseif (!$seoData['meta_description']['is_optimal']) {
            $recommendations[] = 'تحسين طول الوصف التعريفي (120-160 حرف)';
        }
        
        if (!$seoData['headings']['has_single_h1']) {
            $recommendations[] = 'استخدام عنوان H1 واحد فقط في الصفحة';
        }
        
        if ($seoData['images']['alt_percentage'] < 80) {
            $recommendations[] = 'إضافة نص بديل لجميع الصور';
        }
        
        if (!$seoData['content']['is_sufficient']) {
            $recommendations[] = 'زيادة محتوى الصفحة (على الأقل 300 كلمة)';
        }
        
        if (!$seoData['technical']['has_ssl']) {
            $recommendations[] = 'تفعيل شهادة SSL للأمان';
        }
        
        if (!$seoData['social_media']['has_og_tags']) {
            $recommendations[] = 'إضافة Open Graph tags لوسائل التواصل الاجتماعي';
        }
        
        if (!$seoData['structured_data']['has_structured_data']) {
            $recommendations[] = 'إضافة البيانات المنظمة (Schema.org)';
        }
        
        return $recommendations;
    }

    /**
     * فحص robots.txt
     */
    private function checkRobotsTxt($url)
    {
        try {
            $robotsUrl = rtrim($url, '/') . '/robots.txt';
            $response = Http::timeout(10)->get($robotsUrl);
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * فحص sitemap
     */
    private function checkSitemap($url)
    {
        try {
            $sitemapUrl = rtrim($url, '/') . '/sitemap.xml';
            $response = Http::timeout(10)->get($sitemapUrl);
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * حساب نقاط القابلية للقراءة (مبسط)
     */
    private function calculateReadabilityScore($text)
    {
        $sentences = preg_split('/[.!?]+/', $text);
        $sentenceCount = count(array_filter($sentences));
        
        $words = explode(' ', $text);
        $wordCount = count(array_filter($words));
        
        if ($sentenceCount === 0) return 0;
        
        $avgWordsPerSentence = $wordCount / $sentenceCount;
        
        // نقاط مبسطة بناءً على متوسط الكلمات في الجملة
        if ($avgWordsPerSentence <= 15) return 90;
        if ($avgWordsPerSentence <= 20) return 80;
        if ($avgWordsPerSentence <= 25) return 70;
        if ($avgWordsPerSentence <= 30) return 60;
        return 50;
    }
}
