<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use DOMDocument;
use DOMXPath;

class CompetitorAnalyzerService
{
    /**
     * تحليل المنافسين
     */
    public function analyzeCompetitors($url, $region = 'global')
    {
        try {
            // استخراج معلومات الموقع الأساسية
            $siteInfo = $this->extractSiteInfo($url);
            
            // البحث عن المنافسين
            $competitors = $this->findCompetitors($siteInfo, $region);
            
            // تحليل كل منافس
            $competitorAnalysis = [];
            foreach ($competitors as $competitor) {
                $analysis = $this->analyzeCompetitor($competitor, $siteInfo);
                if ($analysis) {
                    $competitorAnalysis[] = $analysis;
                }
            }
            
            // مقارنة مع المنافسين
            $comparison = $this->compareWithCompetitors($url, $competitorAnalysis);
            
            return [
                'competitors' => $competitorAnalysis,
                'comparison' => $comparison,
                'market_analysis' => $this->analyzeMarket($competitorAnalysis, $region),
                'recommendations' => $this->generateCompetitorRecommendations($comparison),
                'total_competitors' => count($competitorAnalysis),
                'region' => $region,
            ];

        } catch (\Exception $e) {
            throw new \Exception('خطأ في تحليل المنافسين: ' . $e->getMessage());
        }
    }

    /**
     * استخراج معلومات الموقع
     */
    private function extractSiteInfo($url)
    {
        try {
            $response = Http::timeout(30)->get($url);
            
            if (!$response->successful()) {
                throw new \Exception('لا يمكن الوصول إلى الموقع');
            }

            $html = $response->body();
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);

            // استخراج الكلمات المفتاحية والمحتوى
            $title = $this->extractTitle($xpath);
            $description = $this->extractMetaDescription($xpath);
            $keywords = $this->extractKeywords($html);
            $industry = $this->detectIndustry($title, $description, $keywords);

            return [
                'url' => $url,
                'domain' => parse_url($url, PHP_URL_HOST),
                'title' => $title,
                'description' => $description,
                'keywords' => $keywords,
                'industry' => $industry,
                'language' => $this->detectLanguage($xpath),
                'content_topics' => $this->extractContentTopics($html),
            ];

        } catch (\Exception $e) {
            return [
                'url' => $url,
                'domain' => parse_url($url, PHP_URL_HOST),
                'title' => '',
                'description' => '',
                'keywords' => [],
                'industry' => 'غير محدد',
                'language' => 'غير محدد',
                'content_topics' => [],
            ];
        }
    }

    /**
     * البحث عن المنافسين
     */
    private function findCompetitors($siteInfo, $region)
    {
        $competitors = [];
        
        // قائمة المنافسين المحتملين بناءً على الصناعة والمنطقة
        $competitorSources = [
            'search_engines' => $this->findCompetitorsViaSearch($siteInfo, $region),
            'similar_domains' => $this->findSimilarDomains($siteInfo),
            'industry_leaders' => $this->getIndustryLeaders($siteInfo['industry'], $region),
        ];

        foreach ($competitorSources as $source => $sourceCompetitors) {
            $competitors = array_merge($competitors, $sourceCompetitors);
        }

        // إزالة التكرارات والموقع نفسه
        $competitors = array_unique($competitors);
        $competitors = array_filter($competitors, function($competitor) use ($siteInfo) {
            return $competitor !== $siteInfo['url'] && $competitor !== $siteInfo['domain'];
        });

        // تحديد العدد الأقصى للمنافسين
        return array_slice($competitors, 0, 10);
    }

    /**
     * البحث عن المنافسين عبر محركات البحث (محاكاة)
     */
    private function findCompetitorsViaSearch($siteInfo, $region)
    {
        // في التطبيق الحقيقي، يمكن استخدام APIs مثل Google Search API
        // هنا سنستخدم قائمة افتراضية بناءً على الصناعة والمنطقة
        
        $competitors = [];
        
        // منافسين افتراضيين بناءً على الصناعة
        $industryCompetitors = [
            'تجارة إلكترونية' => [
                'global' => ['amazon.com', 'ebay.com', 'shopify.com'],
                'middle-east' => ['noon.com', 'souq.com', 'namshi.com'],
                'gcc' => ['carrefouruae.com', 'lulu.ae', 'sharaf-dg.com'],
                'saudi' => ['jarir.com', 'extra.com', 'saco.sa'],
                'uae' => ['carrefouruae.com', 'sharaf-dg.com', 'ounass.ae'],
                'egypt' => ['jumia.com.eg', 'souq.com', 'b-tech.com.eg'],
            ],
            'تقنية' => [
                'global' => ['google.com', 'microsoft.com', 'apple.com'],
                'middle-east' => ['stc.com.sa', 'etisalat.ae', 'du.ae'],
                'gcc' => ['stc.com.sa', 'mobily.com.sa', 'zain.com'],
                'saudi' => ['stc.com.sa', 'mobily.com.sa', 'zain.sa'],
                'uae' => ['etisalat.ae', 'du.ae', 'emirates.com'],
                'egypt' => ['vodafone.com.eg', 'orange.eg', 'we.com.eg'],
            ],
            'إعلام' => [
                'global' => ['cnn.com', 'bbc.com', 'reuters.com'],
                'middle-east' => ['alarabiya.net', 'aljazeera.net', 'skynewsarabia.com'],
                'gcc' => ['albayan.ae', 'alkhaleej.ae', 'alanba.com.kw'],
                'saudi' => ['spa.gov.sa', 'sabq.org', 'okaz.com.sa'],
                'uae' => ['albayan.ae', 'alkhaleej.ae', 'emaratalyoum.com'],
                'egypt' => ['youm7.com', 'masrawy.com', 'elwatan.com'],
            ],
            'تعليم' => [
                'global' => ['coursera.org', 'edx.org', 'udemy.com'],
                'middle-east' => ['rwaq.org', 'edraak.org', 'maharah.net'],
                'gcc' => ['ksu.edu.sa', 'uaeu.ac.ae', 'qu.edu.qa'],
                'saudi' => ['ksu.edu.sa', 'kau.edu.sa', 'kfupm.edu.sa'],
                'uae' => ['uaeu.ac.ae', 'aus.edu', 'adu.ac.ae'],
                'egypt' => ['cu.edu.eg', 'aun.edu.eg', 'asu.edu.eg'],
            ],
        ];

        $industry = $siteInfo['industry'];
        if (isset($industryCompetitors[$industry][$region])) {
            $competitors = $industryCompetitors[$industry][$region];
        } elseif (isset($industryCompetitors[$industry]['global'])) {
            $competitors = $industryCompetitors[$industry]['global'];
        }

        return array_map(function($domain) {
            return strpos($domain, 'http') === 0 ? $domain : 'https://' . $domain;
        }, $competitors);
    }

    /**
     * البحث عن نطاقات مشابهة
     */
    private function findSimilarDomains($siteInfo)
    {
        $domain = $siteInfo['domain'];
        $domainParts = explode('.', $domain);
        $baseName = $domainParts[0];
        
        // إنشاء نطاقات مشابهة افتراضية
        $similarDomains = [];
        
        $variations = [
            $baseName . 'shop.com',
            $baseName . 'store.com',
            $baseName . 'online.com',
            'my' . $baseName . '.com',
            $baseName . 'app.com',
        ];
        
        return array_map(function($domain) {
            return 'https://' . $domain;
        }, array_slice($variations, 0, 3));
    }

    /**
     * الحصول على قادة الصناعة
     */
    private function getIndustryLeaders($industry, $region)
    {
        // قائمة قادة الصناعة حسب المنطقة
        $leaders = [
            'تجارة إلكترونية' => [
                'global' => ['https://amazon.com', 'https://alibaba.com'],
                'middle-east' => ['https://noon.com', 'https://namshi.com'],
                'gcc' => ['https://noon.com', 'https://carrefouruae.com'],
            ],
            'تقنية' => [
                'global' => ['https://google.com', 'https://microsoft.com'],
                'middle-east' => ['https://stc.com.sa', 'https://etisalat.ae'],
                'gcc' => ['https://stc.com.sa', 'https://mobily.com.sa'],
            ],
        ];

        if (isset($leaders[$industry][$region])) {
            return $leaders[$industry][$region];
        } elseif (isset($leaders[$industry]['global'])) {
            return $leaders[$industry]['global'];
        }

        return [];
    }

    /**
     * تحليل منافس واحد
     */
    private function analyzeCompetitor($competitorUrl, $siteInfo)
    {
        try {
            $response = Http::timeout(20)->get($competitorUrl);
            
            if (!$response->successful()) {
                return null;
            }

            $html = $response->body();
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);

            // تحليل أساسي
            $analysis = [
                'url' => $competitorUrl,
                'domain' => parse_url($competitorUrl, PHP_URL_HOST),
                'title' => $this->extractTitle($xpath),
                'description' => $this->extractMetaDescription($xpath),
                'keywords' => $this->extractKeywords($html),
                'content_length' => strlen(strip_tags($html)),
                'load_time' => $this->measureLoadTime($competitorUrl),
                'social_presence' => $this->analyzeSocialPresence($xpath),
                'seo_strength' => $this->estimateSeoStrength($xpath, $html),
                'content_quality' => $this->assessContentQuality($html),
                'technology_stack' => $this->detectTechnologyStack($html, $response->headers()),
                'market_position' => $this->estimateMarketPosition($competitorUrl),
            ];

            return $analysis;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * مقارنة مع المنافسين
     */
    private function compareWithCompetitors($url, $competitorAnalysis)
    {
        // تحليل الموقع الحالي
        $currentSite = $this->analyzeCompetitor($url, []);
        
        if (!$currentSite || empty($competitorAnalysis)) {
            return [
                'position' => 'غير محدد',
                'strengths' => [],
                'weaknesses' => [],
                'opportunities' => [],
            ];
        }

        $comparison = [
            'current_site' => $currentSite,
            'position' => $this->calculateMarketPosition($currentSite, $competitorAnalysis),
            'strengths' => $this->identifyStrengths($currentSite, $competitorAnalysis),
            'weaknesses' => $this->identifyWeaknesses($currentSite, $competitorAnalysis),
            'opportunities' => $this->identifyOpportunities($currentSite, $competitorAnalysis),
            'metrics_comparison' => $this->compareMetrics($currentSite, $competitorAnalysis),
        ];

        return $comparison;
    }

    /**
     * تحليل السوق
     */
    private function analyzeMarket($competitorAnalysis, $region)
    {
        if (empty($competitorAnalysis)) {
            return [
                'market_size' => 'صغير',
                'competition_level' => 'منخفض',
                'trends' => [],
                'opportunities' => [],
            ];
        }

        $avgLoadTime = array_sum(array_column($competitorAnalysis, 'load_time')) / count($competitorAnalysis);
        $avgContentLength = array_sum(array_column($competitorAnalysis, 'content_length')) / count($competitorAnalysis);
        
        $marketAnalysis = [
            'market_size' => count($competitorAnalysis) > 5 ? 'كبير' : 'متوسط',
            'competition_level' => $this->assessCompetitionLevel($competitorAnalysis),
            'avg_load_time' => round($avgLoadTime, 2),
            'avg_content_length' => round($avgContentLength),
            'common_technologies' => $this->findCommonTechnologies($competitorAnalysis),
            'market_trends' => $this->identifyMarketTrends($competitorAnalysis),
            'entry_barriers' => $this->assessEntryBarriers($competitorAnalysis),
        ];

        return $marketAnalysis;
    }

    /**
     * إنشاء توصيات المنافسين
     */
    private function generateCompetitorRecommendations($comparison)
    {
        $recommendations = [];

        if (isset($comparison['weaknesses'])) {
            foreach ($comparison['weaknesses'] as $weakness) {
                switch ($weakness) {
                    case 'سرعة تحميل بطيئة':
                        $recommendations[] = 'تحسين سرعة الموقع لتنافس المواقع الأخرى';
                        break;
                    case 'محتوى قليل':
                        $recommendations[] = 'زيادة كمية وجودة المحتوى';
                        break;
                    case 'ضعف في السيو':
                        $recommendations[] = 'تحسين استراتيجية تحسين محركات البحث';
                        break;
                    case 'قلة الوجود على وسائل التواصل':
                        $recommendations[] = 'تعزيز الوجود على وسائل التواصل الاجتماعي';
                        break;
                }
            }
        }

        if (isset($comparison['opportunities'])) {
            foreach ($comparison['opportunities'] as $opportunity) {
                $recommendations[] = 'استغلال الفرصة: ' . $opportunity;
            }
        }

        // توصيات عامة
        $recommendations[] = 'مراقبة استراتيجيات المنافسين بانتظام';
        $recommendations[] = 'التركيز على نقاط التميز الفريدة';
        $recommendations[] = 'تحليل الكلمات المفتاحية التي يستهدفها المنافسون';

        return array_unique($recommendations);
    }

    // Helper Methods

    private function extractTitle($xpath)
    {
        $titleNodes = $xpath->query('//title');
        return $titleNodes->length > 0 ? trim($titleNodes->item(0)->textContent) : '';
    }

    private function extractMetaDescription($xpath)
    {
        $metaNodes = $xpath->query('//meta[@name="description"]/@content');
        return $metaNodes->length > 0 ? trim($metaNodes->item(0)->textContent) : '';
    }

    private function extractKeywords($html)
    {
        // استخراج الكلمات المفتاحية من المحتوى
        $text = strip_tags($html);
        $words = str_word_count(strtolower($text), 1);
        $wordCounts = array_count_values($words);
        
        // إزالة الكلمات الشائعة
        $stopWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'من', 'في', 'على', 'إلى', 'عن', 'مع', 'هذا', 'هذه', 'التي', 'الذي'];
        foreach ($stopWords as $stopWord) {
            unset($wordCounts[$stopWord]);
        }
        
        arsort($wordCounts);
        return array_slice(array_keys($wordCounts), 0, 10);
    }

    private function detectIndustry($title, $description, $keywords)
    {
        $text = strtolower($title . ' ' . $description . ' ' . implode(' ', $keywords));
        
        $industries = [
            'تجارة إلكترونية' => ['shop', 'store', 'buy', 'sell', 'product', 'cart', 'متجر', 'تسوق', 'شراء'],
            'تقنية' => ['technology', 'software', 'app', 'digital', 'tech', 'تقنية', 'برمجة', 'تطبيق'],
            'إعلام' => ['news', 'media', 'press', 'article', 'أخبار', 'إعلام', 'صحافة'],
            'تعليم' => ['education', 'course', 'learn', 'school', 'university', 'تعليم', 'دورة', 'جامعة'],
            'صحة' => ['health', 'medical', 'doctor', 'hospital', 'صحة', 'طبي', 'مستشفى'],
            'مالية' => ['bank', 'finance', 'money', 'investment', 'بنك', 'مالية', 'استثمار'],
        ];

        foreach ($industries as $industry => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    return $industry;
                }
            }
        }

        return 'عام';
    }

    private function detectLanguage($xpath)
    {
        $langNodes = $xpath->query('//html/@lang');
        if ($langNodes->length > 0) {
            return $langNodes->item(0)->textContent;
        }
        return 'غير محدد';
    }

    private function extractContentTopics($html)
    {
        // استخراج المواضيع الرئيسية من المحتوى
        $text = strip_tags($html);
        $sentences = preg_split('/[.!?]+/', $text);
        
        $topics = [];
        foreach (array_slice($sentences, 0, 5) as $sentence) {
            if (strlen(trim($sentence)) > 20) {
                $topics[] = trim($sentence);
            }
        }
        
        return $topics;
    }

    private function measureLoadTime($url)
    {
        $start = microtime(true);
        try {
            Http::timeout(15)->get($url);
            $end = microtime(true);
            return round($end - $start, 2);
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function analyzeSocialPresence($xpath)
    {
        $socialLinks = $xpath->query('//a[contains(@href, "facebook") or contains(@href, "twitter") or contains(@href, "instagram") or contains(@href, "linkedin") or contains(@href, "youtube")]');
        
        return [
            'has_social_links' => $socialLinks->length > 0,
            'social_count' => $socialLinks->length,
            'platforms' => $this->extractSocialPlatforms($socialLinks),
        ];
    }

    private function extractSocialPlatforms($socialLinks)
    {
        $platforms = [];
        foreach ($socialLinks as $link) {
            $href = $link->getAttribute('href');
            if (strpos($href, 'facebook') !== false) $platforms[] = 'Facebook';
            if (strpos($href, 'twitter') !== false) $platforms[] = 'Twitter';
            if (strpos($href, 'instagram') !== false) $platforms[] = 'Instagram';
            if (strpos($href, 'linkedin') !== false) $platforms[] = 'LinkedIn';
            if (strpos($href, 'youtube') !== false) $platforms[] = 'YouTube';
        }
        return array_unique($platforms);
    }

    private function estimateSeoStrength($xpath, $html)
    {
        $score = 0;
        
        // فحص العنوان
        $title = $this->extractTitle($xpath);
        if (!empty($title) && strlen($title) >= 30 && strlen($title) <= 60) $score += 20;
        
        // فحص الوصف التعريفي
        $description = $this->extractMetaDescription($xpath);
        if (!empty($description) && strlen($description) >= 120 && strlen($description) <= 160) $score += 20;
        
        // فحص H1
        $h1Nodes = $xpath->query('//h1');
        if ($h1Nodes->length === 1) $score += 15;
        
        // فحص الصور مع alt
        $imgNodes = $xpath->query('//img');
        $imgWithAlt = $xpath->query('//img[@alt]');
        if ($imgNodes->length > 0 && $imgWithAlt->length / $imgNodes->length > 0.8) $score += 15;
        
        // فحص المحتوى
        $wordCount = str_word_count(strip_tags($html));
        if ($wordCount > 300) $score += 15;
        
        // فحص الروابط الداخلية
        $internalLinks = $xpath->query('//a[@href]');
        if ($internalLinks->length > 5) $score += 15;
        
        return min($score, 100);
    }

    private function assessContentQuality($html)
    {
        $text = strip_tags($html);
        $wordCount = str_word_count($text);
        $charCount = strlen($text);
        
        $quality = 'منخفض';
        if ($wordCount > 1000 && $charCount > 5000) {
            $quality = 'عالي';
        } elseif ($wordCount > 500 && $charCount > 2500) {
            $quality = 'متوسط';
        }
        
        return [
            'quality' => $quality,
            'word_count' => $wordCount,
            'char_count' => $charCount,
        ];
    }

    private function detectTechnologyStack($html, $headers)
    {
        $technologies = [];
        
        // فحص الخادم
        if (isset($headers['server'])) {
            $technologies['server'] = $headers['server'][0];
        }
        
        // فحص JavaScript frameworks
        if (strpos($html, 'react') !== false) $technologies['frontend'][] = 'React';
        if (strpos($html, 'vue') !== false) $technologies['frontend'][] = 'Vue.js';
        if (strpos($html, 'angular') !== false) $technologies['frontend'][] = 'Angular';
        if (strpos($html, 'jquery') !== false) $technologies['frontend'][] = 'jQuery';
        
        // فحص CSS frameworks
        if (strpos($html, 'bootstrap') !== false) $technologies['css'][] = 'Bootstrap';
        if (strpos($html, 'tailwind') !== false) $technologies['css'][] = 'Tailwind CSS';
        
        return $technologies;
    }

    private function estimateMarketPosition($url)
    {
        // تقدير مبسط لموقع السوق بناءً على النطاق
        $domain = parse_url($url, PHP_URL_HOST);
        
        if (in_array($domain, ['google.com', 'amazon.com', 'facebook.com', 'microsoft.com'])) {
            return 'قائد السوق';
        } elseif (strlen($domain) < 10) {
            return 'قوي';
        } else {
            return 'متوسط';
        }
    }

    private function calculateMarketPosition($currentSite, $competitors)
    {
        if (empty($competitors)) return 'غير محدد';
        
        $currentScore = $currentSite['seo_strength'] ?? 0;
        $competitorScores = array_column($competitors, 'seo_strength');
        
        $betterThan = 0;
        foreach ($competitorScores as $score) {
            if ($currentScore > $score) $betterThan++;
        }
        
        $percentage = ($betterThan / count($competitors)) * 100;
        
        if ($percentage >= 80) return 'متقدم';
        if ($percentage >= 60) return 'قوي';
        if ($percentage >= 40) return 'متوسط';
        return 'يحتاج تحسين';
    }

    private function identifyStrengths($currentSite, $competitors)
    {
        $strengths = [];
        
        if (empty($competitors)) return $strengths;
        
        $avgLoadTime = array_sum(array_column($competitors, 'load_time')) / count($competitors);
        $avgContentLength = array_sum(array_column($competitors, 'content_length')) / count($competitors);
        $avgSeoStrength = array_sum(array_column($competitors, 'seo_strength')) / count($competitors);
        
        if (($currentSite['load_time'] ?? 0) < $avgLoadTime) {
            $strengths[] = 'سرعة تحميل أفضل من المنافسين';
        }
        
        if (($currentSite['content_length'] ?? 0) > $avgContentLength) {
            $strengths[] = 'محتوى أكثر شمولية';
        }
        
        if (($currentSite['seo_strength'] ?? 0) > $avgSeoStrength) {
            $strengths[] = 'تحسين محركات البحث أقوى';
        }
        
        return $strengths;
    }

    private function identifyWeaknesses($currentSite, $competitors)
    {
        $weaknesses = [];
        
        if (empty($competitors)) return $weaknesses;
        
        $avgLoadTime = array_sum(array_column($competitors, 'load_time')) / count($competitors);
        $avgContentLength = array_sum(array_column($competitors, 'content_length')) / count($competitors);
        $avgSeoStrength = array_sum(array_column($competitors, 'seo_strength')) / count($competitors);
        
        if (($currentSite['load_time'] ?? 0) > $avgLoadTime) {
            $weaknesses[] = 'سرعة تحميل بطيئة';
        }
        
        if (($currentSite['content_length'] ?? 0) < $avgContentLength) {
            $weaknesses[] = 'محتوى قليل';
        }
        
        if (($currentSite['seo_strength'] ?? 0) < $avgSeoStrength) {
            $weaknesses[] = 'ضعف في السيو';
        }
        
        return $weaknesses;
    }

    private function identifyOpportunities($currentSite, $competitors)
    {
        $opportunities = [];
        
        // تحليل الفجوات في السوق
        $commonTechnologies = $this->findCommonTechnologies($competitors);
        $socialPresence = array_column($competitors, 'social_presence');
        
        if (count($commonTechnologies) < 3) {
            $opportunities[] = 'استخدام تقنيات حديثة غير مستغلة';
        }
        
        $hasSocial = false;
        foreach ($socialPresence as $social) {
            if ($social['has_social_links'] ?? false) {
                $hasSocial = true;
                break;
            }
        }
        
        if (!$hasSocial) {
            $opportunities[] = 'تعزيز الوجود على وسائل التواصل الاجتماعي';
        }
        
        $opportunities[] = 'تحسين تجربة المستخدم';
        $opportunities[] = 'استهداف كلمات مفتاحية جديدة';
        
        return $opportunities;
    }

    private function compareMetrics($currentSite, $competitors)
    {
        if (empty($competitors)) return [];
        
        return [
            'load_time' => [
                'current' => $currentSite['load_time'] ?? 0,
                'average' => round(array_sum(array_column($competitors, 'load_time')) / count($competitors), 2),
                'best' => min(array_column($competitors, 'load_time')),
            ],
            'seo_strength' => [
                'current' => $currentSite['seo_strength'] ?? 0,
                'average' => round(array_sum(array_column($competitors, 'seo_strength')) / count($competitors)),
                'best' => max(array_column($competitors, 'seo_strength')),
            ],
            'content_length' => [
                'current' => $currentSite['content_length'] ?? 0,
                'average' => round(array_sum(array_column($competitors, 'content_length')) / count($competitors)),
                'best' => max(array_column($competitors, 'content_length')),
            ],
        ];
    }

    private function assessCompetitionLevel($competitors)
    {
        if (count($competitors) > 8) return 'عالي';
        if (count($competitors) > 4) return 'متوسط';
        return 'منخفض';
    }

    private function findCommonTechnologies($competitors)
    {
        $allTechnologies = [];
        
        foreach ($competitors as $competitor) {
            if (isset($competitor['technology_stack'])) {
                foreach ($competitor['technology_stack'] as $category => $techs) {
                    if (is_array($techs)) {
                        $allTechnologies = array_merge($allTechnologies, $techs);
                    } else {
                        $allTechnologies[] = $techs;
                    }
                }
            }
        }
        
        $techCounts = array_count_values($allTechnologies);
        arsort($techCounts);
        
        return array_slice(array_keys($techCounts), 0, 5);
    }

    private function identifyMarketTrends($competitors)
    {
        $trends = [];
        
        // تحليل الاتجاهات بناءً على التقنيات المستخدمة
        $technologies = $this->findCommonTechnologies($competitors);
        
        if (in_array('React', $technologies)) {
            $trends[] = 'اتجاه نحو استخدام React في الواجهات الأمامية';
        }
        
        if (in_array('Bootstrap', $technologies)) {
            $trends[] = 'استخدام واسع لـ Bootstrap في التصميم';
        }
        
        // تحليل الوجود على وسائل التواصل
        $socialCount = 0;
        foreach ($competitors as $competitor) {
            if (isset($competitor['social_presence']['has_social_links']) && $competitor['social_presence']['has_social_links']) {
                $socialCount++;
            }
        }
        
        if ($socialCount > count($competitors) * 0.7) {
            $trends[] = 'اهتمام كبير بوسائل التواصل الاجتماعي';
        }
        
        return $trends;
    }

    private function assessEntryBarriers($competitors)
    {
        $avgSeoStrength = array_sum(array_column($competitors, 'seo_strength')) / count($competitors);
        
        if ($avgSeoStrength > 80) return 'عالية';
        if ($avgSeoStrength > 60) return 'متوسطة';
        return 'منخفضة';
    }
}
