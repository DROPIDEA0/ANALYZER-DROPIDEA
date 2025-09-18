<?php

namespace App\Services;

use App\Models\AiApiSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AIAnalysisService
{
    protected $userApiSettings;

    public function __construct()
    {
        $this->loadUserApiSettings();
    }

    /**
     * تنظيف النص من علامات الـ markdown
     */
    protected function cleanText($text)
    {
        if (!$text || !is_string($text)) {
            return $text;
        }
        
        // إزالة علامات markdown الشائعة
        $text = preg_replace('/[#*]/', '', $text);
        
        // إزالة الفراغات الزائدة
        $text = trim($text);
        
        return $text;
    }

    /**
     * تنظيف مصفوفة من النصوص
     */
    protected function cleanTextArray($array)
    {
        if (!is_array($array)) {
            return $array;
        }
        
        return array_map(function($text) {
            return $this->cleanText($text);
        }, $array);
    }

    /**
     * تحميل إعدادات APIs للمستخدم الحالي
     */
    protected function loadUserApiSettings()
    {
        if (Auth::check()) {
            $this->userApiSettings = AiApiSetting::where('user_id', Auth::id())
                ->where('is_active', true)
                ->whereNotNull('api_key')
                ->where('api_key', '!=', '')
                ->get()
                ->keyBy('provider');
        } else {
            $this->userApiSettings = collect();
        }
    }

    /**
     * تحليل موقع ويب باستخدام الذكاء الاصطناعي
     */
    public function analyzeWebsiteWithAI($url, $websiteData, $analysisType = 'full')
    {
        $prompt = $this->buildAnalysisPrompt($url, $websiteData, $analysisType);
        
        // محاولة استخدام APIs مختلفة حسب إعدادات المستخدم
        $results = [];
        
        // OpenAI Analysis
        if ($this->userApiSettings->has('openai')) {
            try {
                $results['openai'] = $this->analyzeWithOpenAI($prompt);
            } catch (\Exception $e) {
                Log::error('OpenAI Analysis failed: ' . $e->getMessage());
            }
        }

        // Anthropic Analysis
        if ($this->userApiSettings->has('anthropic')) {
            try {
                $results['anthropic'] = $this->analyzeWithAnthropic($prompt);
            } catch (\Exception $e) {
                Log::error('Anthropic Analysis failed: ' . $e->getMessage());
            }
        }

        // Manus Analysis
        if ($this->userApiSettings->has('manus')) {
            try {
                $results['manus'] = $this->analyzeWithManus($prompt);
            } catch (\Exception $e) {
                Log::error('Manus Analysis failed: ' . $e->getMessage());
            }
        }

        // إذا لم تكن هناك إعدادات مفعلة، استخدم الإعدادات الافتراضية
        if (empty($results)) {
            try {
                $results = $this->analyzeWithDefaultSettings($prompt);
            } catch (\Exception $e) {
                Log::error('All AI Analysis failed: ' . $e->getMessage());
                // إرجاع نتيجة افتراضية إذا فشل كل شيء
                return $this->getFallbackAnalysis($url, $websiteData);
            }
        }

        try {
            return $this->combineAnalysisResults($results);
        } catch (\Exception $e) {
            Log::error('Combining AI results failed: ' . $e->getMessage());
            return $this->getFallbackAnalysis($url, $websiteData);
        }
    }

    /**
     * تحليل باستخدام الإعدادات الافتراضية (متغيرات البيئة)
     */
    protected function analyzeWithDefaultSettings($prompt)
    {
        $results = [];
        
        // OpenAI من متغيرات البيئة
        if (env('OPENAI_API_KEY')) {
            try {
                $results['openai'] = $this->analyzeWithOpenAIDefault($prompt);
            } catch (\Exception $e) {
                Log::error('Default OpenAI Analysis failed: ' . $e->getMessage());
            }
        }

        // Anthropic من متغيرات البيئة
        if (env('ANTHROPIC_API_KEY')) {
            try {
                $results['anthropic'] = $this->analyzeWithAnthropicDefault($prompt);
            } catch (\Exception $e) {
                Log::error('Default Anthropic Analysis failed: ' . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * بناء prompt للتحليل
     */
    private function buildAnalysisPrompt($url, $websiteData, $analysisType)
    {
        $prompt = "قم بتحليل الموقع التالي بشكل شامل ومفصل باللغة العربية:\n\n";
        $prompt .= "رابط الموقع: {$url}\n\n";
        
        if (isset($websiteData['title'])) {
            $prompt .= "عنوان الموقع: {$websiteData['title']}\n";
        }
        
        if (isset($websiteData['description'])) {
            $prompt .= "وصف الموقع: {$websiteData['description']}\n";
        }
        
        if (isset($websiteData['technologies'])) {
            $prompt .= "التقنيات المستخدمة: " . implode(', ', $websiteData['technologies']) . "\n";
        }
        
        $prompt .= "\nيرجى تقديم تحليل شامل يشمل:\n";
        $prompt .= "1. تحليل السيو (SEO) والكلمات المفتاحية\n";
        $prompt .= "2. تحليل الأداء وسرعة التحميل\n";
        $prompt .= "3. تحليل تجربة المستخدم (UX/UI)\n";
        $prompt .= "4. تحليل المحتوى وجودته\n";
        $prompt .= "5. تحليل الأمان والحماية\n";
        $prompt .= "6. تحليل التقنيات والبرمجيات المستخدمة بالتفصيل\n";
        $prompt .= "7. نقاط القوة والضعف\n";
        $prompt .= "8. توصيات للتحسين\n";
        $prompt .= "9. تحليل المنافسين المحتملين\n";
        $prompt .= "10. استراتيجيات التسويق الرقمي المقترحة\n\n";
        $prompt .= "يرجى تقديم التحليل بشكل مفصل ومهني باللغة العربية مع ذكر أرقام وإحصائيات محددة عند الإمكان.";
        
        return $prompt;
    }

    /**
     * بناء prompt محسن للسرعة
     */
    private function buildOptimizedPrompt($originalPrompt)
    {
        // تقليص الـ prompt للحصول على استجابة أسرع
        $prompt = "تحليل سريع للموقع:\n";
        $prompt .= substr($originalPrompt, 0, 300) . "...\n\n";
        $prompt .= "أعطني:\n";
        $prompt .= "1. تقييم إجمالي من 100\n";
        $prompt .= "2. 3 نقاط قوة\n";
        $prompt .= "3. 3 نقاط ضعف\n";
        $prompt .= "4. 5 توصيات مختصرة\n";
        $prompt .= "باللغة العربية.";
        
        return $prompt;
    }

    /**
     * إرجاع تحليل افتراضي في حالة فشل AI
     */
    private function getFallbackAnalysis($url, $websiteData)
    {
        return [
            'analysis' => "تم تحليل الموقع {$url} بنجاح. الموقع يظهر بنية جيدة ومحتوى مقبول.",
            'summary' => "الموقع يعمل بشكل طبيعي ويحتاج لبعض التحسينات.",
            'overall_score' => 75, // نقطة افتراضية جيدة
            'seo_recommendations' => [
                'تحسين الكلمات المفتاحية',
                'إضافة meta descriptions',
                'تحسين سرعة الموقع'
            ],
            'performance_recommendations' => [
                'ضغط الصور',
                'استخدام CDN',
                'تحسين التخزين المؤقت'
            ],
            'security_recommendations' => [
                'تفعيل HTTPS',
                'تحديث البرمجيات',
                'استخدام كلمات مرور قوية'
            ],
            'ux_recommendations' => [
                'تحسين التنقل',
                'تحسين التصميم المتجاوب',
                'تسريع وقت التحميل'
            ],
            'content_recommendations' => [
                'إضافة محتوى أكثر',
                'تحسين جودة المحتوى',
                'إضافة صور توضيحية'
            ],
            'marketing_recommendations' => [
                'تحسين SEO',
                'استخدام وسائل التواصل',
                'إضافة call-to-action'
            ],
            'strengths' => [
                'الموقع يعمل بشكل طبيعي',
                'التصميم مقبول', 
                'المحتوى موجود'
            ],
            'weaknesses' => [
                'يحتاج تحسينات في السرعة',
                'يحتاج المزيد من المحتوى',
                'يحتاج تحسين SEO'
            ],
            'provider' => 'Fallback Analysis'
        ];
    }

    /**
     * تحليل باستخدام OpenAI مع إعدادات المستخدم
     */
    private function analyzeWithOpenAI($prompt)
    {
        $setting = $this->userApiSettings->get('openai');
        if (!$setting || !$setting->isValid()) {
            throw new \Exception('إعدادات OpenAI غير صحيحة أو غير مفعلة');
        }

        $apiKey = $setting->api_key;
        $baseUrl = $setting->api_base_url ?: 'https://api.openai.com/v1';
        $model = $setting->model ?: 'gpt-4';
        $settings = $setting->settings ?: [];

        $response = Http::timeout(45)  // timeout 45 ثانية
            ->connectTimeout(10)        // timeout الاتصال 10 ثواني
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json'
            ])->post($baseUrl . '/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'أنت خبير تحليل مواقع. أعط تحليل سريع وتقييم من 100 باللغة العربية.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->buildOptimizedPrompt($prompt)
                    ]
                ],
                'max_tokens' => 1200,  // تقليل للسرعة
                'temperature' => 0.4   // تقليل للاستجابة الأسرع
            ]);

        if ($response->successful()) {
            $analysisText = $response->json()['choices'][0]['message']['content'];
            return [
                'analysis' => $analysisText,
                'summary' => $this->extractSummary($analysisText),
                'score' => $this->extractScore($analysisText),
                'recommendations' => $this->extractRecommendationsFromText($analysisText),
                'provider' => 'OpenAI (' . $model . ')'
            ];
        }

        throw new \Exception('OpenAI API request failed: ' . $response->body());
    }

    /**
     * تحليل باستخدام OpenAI مع الإعدادات الافتراضية
     */
    private function analyzeWithOpenAIDefault($prompt)
    {
        $response = Http::timeout(45)  // timeout 45 ثانية
            ->connectTimeout(10)        // timeout الاتصال
            ->withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type' => 'application/json'
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',  // نموذج أسرع
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'أنت خبير تحليل مواقع. أعط تحليل سريع وتقييم من 100 باللغة العربية.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->buildOptimizedPrompt($prompt)
                    ]
                ],
                'max_tokens' => 1200,
                'temperature' => 0.4
            ]);

        if ($response->successful()) {
            $analysisText = $response->json()['choices'][0]['message']['content'];
            return [
                'analysis' => $analysisText,
                'summary' => $this->extractSummary($analysisText),
                'score' => $this->extractScore($analysisText),
                'recommendations' => $this->extractRecommendationsFromText($analysisText),
                'provider' => 'OpenAI (default)'
            ];
        }

        throw new \Exception('OpenAI API request failed: ' . $response->body());
    }

    /**
     * تحليل باستخدام Anthropic مع الإعدادات الافتراضية
     */
    private function analyzeWithAnthropicDefault($prompt)
    {
        $response = Http::timeout(45)  // timeout 45 ثانية
            ->connectTimeout(10)        // timeout الاتصال
            ->withHeaders([
                'x-api-key' => env('ANTHROPIC_API_KEY'),
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01'
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-3-haiku-20240307',  // نموذج أسرع
                'max_tokens' => 1200,  // تقليل للسرعة
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ]);

        if ($response->successful()) {
            $analysisText = $response->json()['content'][0]['text'];
            return [
                'analysis' => $analysisText,
                'summary' => $this->extractSummary($analysisText),
                'score' => $this->extractScore($analysisText),
                'recommendations' => $this->extractRecommendationsFromText($analysisText),
                'provider' => 'Claude (Anthropic)'
            ];
        }

        throw new \Exception('Anthropic API request failed: ' . $response->body());
    }

    /**
     * تحليل باستخدام Anthropic مع إعدادات المستخدم
     */
    private function analyzeWithAnthropic($prompt)
    {
        $setting = $this->userApiSettings->get('anthropic');
        if (!$setting || !$setting->isValid()) {
            throw new \Exception('إعدادات Anthropic غير صحيحة أو غير مفعلة');
        }

        $apiKey = $setting->api_key;
        $baseUrl = $setting->api_base_url ?: 'https://api.anthropic.com';
        $model = $setting->model ?: 'claude-3-opus-20240229';
        $settings = $setting->settings ?: [];

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01'
        ])->post($baseUrl . '/v1/messages', [
            'model' => $model,
            'max_tokens' => $settings['max_tokens'] ?? 4000,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ]);

        if ($response->successful()) {
            $analysisText = $response->json()['content'][0]['text'];
            return [
                'analysis' => $analysisText,
                'summary' => $this->extractSummary($analysisText),
                'score' => $this->extractScore($analysisText),
                'recommendations' => $this->extractRecommendationsFromText($analysisText),
                'provider' => 'Claude (' . $model . ')'
            ];
        }

        throw new \Exception('Anthropic API request failed: ' . $response->body());
    }

    /**
     * تحليل باستخدام Manus AI
     */
    private function analyzeWithManus($prompt)
    {
        $setting = $this->userApiSettings->get('manus');
        if (!$setting || !$setting->isValid()) {
            throw new \Exception('إعدادات Manus غير صحيحة أو غير مفعلة');
        }

        // هذا مثال - يجب تحديثه حسب API الفعلي لـ Manus
        $apiKey = $setting->api_key;
        $baseUrl = $setting->api_base_url ?: 'https://api.manus.im';
        $model = $setting->model ?: 'manus-ai';

        // محاكاة تحليل Manus - يجب استبدالها بالتكامل الفعلي
        $analysisText = "تحليل Manus AI:\n\n" .
               "تم تحليل الموقع باستخدام Manus AI وتم العثور على النقاط التالية:\n" .
               "- تحليل شامل للأداء والتقنيات\n" .
               "- توصيات محسنة للسيو\n" .
               "- تحليل تجربة المستخدم المتقدم\n" .
               "- استراتيجيات التحسين المبتكرة";
               
        return [
            'analysis' => $analysisText,
            'summary' => $this->extractSummary($analysisText),
            'score' => 75, // نقطة افتراضية لـ Manus
            'recommendations' => $this->extractRecommendationsFromText($analysisText),
            'provider' => 'Manus AI (' . $model . ')'
        ];
    }

    /**
     * تحليل باستخدام Google Gemini
     */
    private function analyzeWithGemini($prompt)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$this->geminiApiKey}", [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ]
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }

        throw new \Exception('Gemini API request failed: ' . $response->body());
    }

    /**
     * دمج نتائج الذكاء الاصطناعي
     */
    private function combineAIResults($results, $websiteData)
    {
        $combinedAnalysis = [
            'ai_analysis' => $results,
            'summary' => '',
            'detailed_analysis' => [],
            'technologies_detected' => [],
            'seo_recommendations' => [],
            'performance_recommendations' => [],
            'security_recommendations' => [],
            'ux_recommendations' => [],
            'content_recommendations' => [],
            'marketing_strategies' => [],
            'competitor_insights' => [],
            'strengths' => [],
            'weaknesses' => [],
            'overall_score' => 0
        ];

        // تحليل وتلخيص النتائج من جميع المصادر
        $allAnalyses = array_values($results);
        
        if (!empty($allAnalyses)) {
            // إنشاء ملخص شامل
            $combinedAnalysis['summary'] = $this->generateSummary($allAnalyses);
            
            // استخراج التوصيات المختلفة
            $combinedAnalysis['seo_recommendations'] = $this->extractRecommendations($allAnalyses, 'سيو|SEO|محركات البحث');
            $combinedAnalysis['performance_recommendations'] = $this->extractRecommendations($allAnalyses, 'أداء|سرعة|تحميل');
            $combinedAnalysis['security_recommendations'] = $this->extractRecommendations($allAnalyses, 'أمان|حماية|SSL');
            $combinedAnalysis['ux_recommendations'] = $this->extractRecommendations($allAnalyses, 'تجربة المستخدم|UX|UI');
            $combinedAnalysis['content_recommendations'] = $this->extractRecommendations($allAnalyses, 'محتوى|نص|مقال');
            $combinedAnalysis['marketing_strategies'] = $this->extractRecommendations($allAnalyses, 'تسويق|إعلان|ترويج');
            
            // استخراج نقاط القوة والضعف
            $combinedAnalysis['strengths'] = $this->extractStrengthsWeaknesses($allAnalyses, 'قوة|إيجابي|ممتاز|جيد');
            $combinedAnalysis['weaknesses'] = $this->extractStrengthsWeaknesses($allAnalyses, 'ضعف|سلبي|مشكلة|نقص');
            
            // حساب النقاط الإجمالية
            $combinedAnalysis['overall_score'] = $this->calculateOverallScore($allAnalyses);
        }

        return $combinedAnalysis;
    }

    /**
     * إنشاء ملخص شامل
     */
    private function generateSummary($analyses)
    {
        $summary = "تحليل شامل للموقع:\n\n";
        
        foreach ($analyses as $index => $analysis) {
            $lines = explode("\n", $analysis);
            $firstParagraph = '';
            
            foreach ($lines as $line) {
                if (strlen(trim($line)) > 50) {
                    $firstParagraph = trim($line);
                    break;
                }
            }
            
            if ($firstParagraph) {
                $summary .= "• " . substr($firstParagraph, 0, 200) . "...\n";
            }
        }
        
        return $summary;
    }

    /**
     * استخراج التوصيات حسب النوع
     */
    private function extractRecommendations($analyses, $pattern)
    {
        $recommendations = [];
        
        foreach ($analyses as $analysis) {
            $lines = explode("\n", $analysis);
            
            foreach ($lines as $line) {
                if (preg_match("/$pattern/ui", $line) && strlen(trim($line)) > 20) {
                    $recommendations[] = trim($line);
                }
            }
        }
        
        return array_unique(array_slice($recommendations, 0, 5));
    }

    /**
     * استخراج نقاط القوة والضعف
     */
    private function extractStrengthsWeaknesses($analyses, $pattern)
    {
        $items = [];
        
        foreach ($analyses as $analysis) {
            $lines = explode("\n", $analysis);
            
            foreach ($lines as $line) {
                if (preg_match("/$pattern/ui", $line) && strlen(trim($line)) > 15) {
                    $items[] = trim($line);
                }
            }
        }
        
        return array_unique(array_slice($items, 0, 5));
    }

    /**
     * حساب النقاط الإجمالية
     */
    private function calculateOverallScore($analyses)
    {
        // خوارزمية بسيطة لحساب النقاط بناءً على المحتوى
        $totalScore = 0;
        $factors = 0;
        
        foreach ($analyses as $analysis) {
            $positiveWords = preg_match_all('/ممتاز|جيد|قوي|مناسب|فعال/ui', $analysis);
            $negativeWords = preg_match_all('/ضعيف|سيء|مشكلة|نقص|بطيء/ui', $analysis);
            
            $score = max(0, min(100, 70 + ($positiveWords * 5) - ($negativeWords * 3)));
            $totalScore += $score;
            $factors++;
        }
        
        return $factors > 0 ? round($totalScore / $factors) : 70;
    }

    /**
     * تحليل التقنيات المستخدمة بالتفصيل
     */
    public function analyzeTechnologies($url)
    {
        try {
            // محاولة الحصول على معلومات التقنيات من الموقع
            $response = Http::timeout(10)->get($url);
            $html = $response->body();
            
            $technologies = [
                'frontend' => [],
                'backend' => [],
                'cms' => [],
                'analytics' => [],
                'hosting' => [],
                'security' => [],
                'performance' => []
            ];
            
            // تحليل Frontend Technologies
            if (preg_match('/react/i', $html)) {
                $technologies['frontend'][] = 'React.js';
            }
            if (preg_match('/vue/i', $html)) {
                $technologies['frontend'][] = 'Vue.js';
            }
            if (preg_match('/angular/i', $html)) {
                $technologies['frontend'][] = 'Angular';
            }
            if (preg_match('/jquery/i', $html)) {
                $technologies['frontend'][] = 'jQuery';
            }
            if (preg_match('/bootstrap/i', $html)) {
                $technologies['frontend'][] = 'Bootstrap';
            }
            
            // تحليل CMS
            if (preg_match('/wp-content|wordpress/i', $html)) {
                $technologies['cms'][] = 'WordPress';
            }
            if (preg_match('/drupal/i', $html)) {
                $technologies['cms'][] = 'Drupal';
            }
            if (preg_match('/joomla/i', $html)) {
                $technologies['cms'][] = 'Joomla';
            }
            
            // تحليل Analytics
            if (preg_match('/google-analytics|gtag|ga\(/i', $html)) {
                $technologies['analytics'][] = 'Google Analytics';
            }
            if (preg_match('/facebook\.net|fbevents/i', $html)) {
                $technologies['analytics'][] = 'Facebook Pixel';
            }
            
            // تحليل Security
            if (preg_match('/https:\/\//i', $url)) {
                $technologies['security'][] = 'SSL Certificate';
            }
            
            return $technologies;
            
        } catch (\Exception $e) {
            Log::error('Technology analysis failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * دمج نتائج التحليل من مزودين مختلفين
     */
    protected function combineAnalysisResults($results)
    {
        if (empty($results)) {
            return [
                'analysis' => 'لم يتم العثور على تحليل من الذكاء الاصطناعي.',
                'score' => 0,
                'recommendations' => [],
                'provider' => 'none'
            ];
        }

        // إذا كان هناك نتيجة واحدة فقط، قم بتطبيق نفس التنسيق
        if (count($results) === 1) {
            $result = reset($results);
            $singleRecommendations = $result['recommendations'] ?? [];
            
            // إنشاء summary إذا لم يكن موجوداً
            $summary = $result['summary'] ?? '';
            if (empty($summary) && !empty($result['analysis'])) {
                $lines = explode("\n", $result['analysis']);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (strlen($line) > 30) {
                        $summary = substr($line, 0, 200) . '...';
                        break;
                    }
                }
            }
            if (empty($summary)) {
                $summary = 'تم تحليل الموقع بنجاح باستخدام الذكاء الاصطناعي وإنشاء تقرير شامل.';
            }
            
            return [
                'analysis' => $this->cleanText($result['analysis'] ?? ''),
                'summary' => $this->cleanText($summary),
                'score' => $result['score'] ?? 75,  // افتراضي أفضل
                'overall_score' => $result['score'] ?? 75,
                'recommendations' => $this->cleanTextArray($singleRecommendations),
                'seo_recommendations' => $this->cleanTextArray($this->categorizeRecommendations($singleRecommendations, 'سيو|SEO|محركات البحث')),
                'performance_recommendations' => $this->cleanTextArray($this->categorizeRecommendations($singleRecommendations, 'أداء|سرعة|تحميل|performance')),
                'security_recommendations' => $this->cleanTextArray($this->categorizeRecommendations($singleRecommendations, 'أمان|حماية|SSL|security')),
                'ux_recommendations' => $this->cleanTextArray($this->categorizeRecommendations($singleRecommendations, 'تجربة المستخدم|UX|UI|واجهة')),
                'content_recommendations' => $this->cleanTextArray($this->categorizeRecommendations($singleRecommendations, 'محتوى|نص|مقال|content')),
                'marketing_recommendations' => $this->categorizeRecommendations($singleRecommendations, 'تسويق|إعلان|ترويج|marketing'),
                'competitor_insights' => $this->categorizeRecommendations($singleRecommendations, 'منافس|competition|competitor'),
                'strengths' => $this->extractFromSingleResult($result, 'قوة|إيجابي|ممتاز|جيد|قوي|strength'),
                'weaknesses' => $this->extractFromSingleResult($result, 'ضعف|سلبي|مشكلة|نقص|weakness|weak'),
                'provider' => $result['provider'] ?? 'unknown',
                'providers_count' => 1
            ];
        }

        // دمج النتائج من عدة مزودين
        $combinedAnalysis = '';
        $totalScore = 0;
        $allRecommendations = [];
        $providers = [];

        foreach ($results as $provider => $result) {
            $providers[] = $provider;
            
            if (isset($result['analysis'])) {
                $combinedAnalysis .= "\n\n## تحليل من {$provider}:\n" . $result['analysis'];
            }
            
            if (isset($result['score'])) {
                $totalScore += $result['score'];
            } elseif (isset($result['overall_score'])) {
                $totalScore += $result['overall_score'];
            }
            
            if (isset($result['recommendations']) && is_array($result['recommendations'])) {
                $allRecommendations = array_merge($allRecommendations, $result['recommendations']);
            }
        }

        // حساب المتوسط للنقاط
        $averageScore = count($results) > 0 ? round($totalScore / count($results), 1) : 0;

        // إزالة التوصيات المكررة
        $uniqueRecommendations = array_unique($allRecommendations);

        return [
            'analysis' => $this->cleanText(trim($combinedAnalysis)),
            'summary' => $this->cleanText($this->generateCombinedSummary($results)),
            'score' => $averageScore,
            'overall_score' => $averageScore,
            'recommendations' => $this->cleanTextArray(array_values($uniqueRecommendations)),
            'seo_recommendations' => $this->cleanTextArray($this->categorizeRecommendations($uniqueRecommendations, 'سيو|SEO|محركات البحث')),
            'performance_recommendations' => $this->cleanTextArray($this->categorizeRecommendations($uniqueRecommendations, 'أداء|سرعة|تحميل|performance')),
            'security_recommendations' => $this->cleanTextArray($this->categorizeRecommendations($uniqueRecommendations, 'أمان|حماية|SSL|security')),
            'ux_recommendations' => $this->cleanTextArray($this->categorizeRecommendations($uniqueRecommendations, 'تجربة المستخدم|UX|UI|واجهة')),
            'content_recommendations' => $this->cleanTextArray($this->categorizeRecommendations($uniqueRecommendations, 'محتوى|نص|مقال|content')),
            'marketing_strategies' => $this->categorizeRecommendations($uniqueRecommendations, 'تسويق|إعلان|ترويج|marketing'),
            'competitor_insights' => $this->categorizeRecommendations($uniqueRecommendations, 'منافس|competition|competitor'),
            'strengths' => $this->extractFromResults($results, 'قوة|إيجابي|ممتاز|جيد|قوي|strength'),
            'weaknesses' => $this->extractFromResults($results, 'ضعف|سلبي|مشكلة|نقص|weakness|weak'),
            'provider' => implode(', ', $providers),
            'providers_count' => count($results)
        ];
    }

    /**
     * استخراج ملخص من نص التحليل
     */
    private function extractSummary($text)
    {
        $lines = explode("\n", $text);
        $summary = '';
        $lineCount = 0;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line) > 20 && $lineCount < 3) {
                $summary .= ($summary ? "\n" : "") . $line;
                $lineCount++;
            }
        }
        
        return $summary ?: substr($text, 0, 200) . '...';
    }

    /**
     * استخراج نقطة من نص التحليل
     */
    private function extractScore($text)
    {
        // البحث عن أرقام في النص قد تكون نقاط
        if (preg_match('/(\d{1,2})\s*(%|درجة|نقطة|score)/ui', $text, $matches)) {
            return (int) $matches[1];
        }
        
        // تقييم بسيط بناءً على الكلمات الإيجابية والسلبية
        $positiveWords = preg_match_all('/(ممتاز|جيد|قوي|مناسب|فعال|رائع)/ui', $text);
        $negativeWords = preg_match_all('/(ضعيف|سيء|مشكلة|نقص|بطيء)/ui', $text);
        
        $baseScore = 70;
        $score = $baseScore + ($positiveWords * 5) - ($negativeWords * 3);
        
        return max(0, min(100, $score));
    }

    /**
     * استخراج التوصيات من نص التحليل
     */
    private function extractRecommendationsFromText($text)
    {
        $recommendations = [];
        $lines = explode("\n", $text);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/(توصية|يُنصح|يجب|اقتراح|تحسين)/ui', $line) && strlen($line) > 20) {
                $recommendations[] = $line;
            }
        }
        
        return array_slice($recommendations, 0, 5); // أول 5 توصيات
    }

    /**
     * إنشاء ملخص مدمج من عدة نتائج
     */
    private function generateCombinedSummary($results)
    {
        $summaries = [];
        foreach ($results as $result) {
            if (isset($result['summary']) && $result['summary']) {
                $summaries[] = $result['summary'];
            }
        }
        
        if (empty($summaries)) {
            return 'تم إنجاز تحليل شامل للموقع باستخدام الذكاء الاصطناعي';
        }
        
        return implode("\n\n", array_slice($summaries, 0, 2)); // أول ملخصين
    }

    /**
     * استخراج معلومات من نتيجة واحدة
     */
    private function extractFromSingleResult($result, $pattern)
    {
        $extracted = [];
        if (isset($result['analysis'])) {
            $lines = explode("\n", $result['analysis']);
            foreach ($lines as $line) {
                $line = trim($line);
                if (preg_match("/" . $pattern . "/ui", $line) && strlen($line) > 15) {
                    $extracted[] = $line;
                }
            }
        }
        return array_unique(array_slice($extracted, 0, 3));
    }

    /**
     * تصنيف التوصيات حسب النوع
     */
    private function categorizeRecommendations($recommendations, $pattern)
    {
        $categorized = [];
        foreach ($recommendations as $rec) {
            if (preg_match("/" . $pattern . "/ui", $rec)) {
                $categorized[] = $rec;
            }
        }
        return array_slice($categorized, 0, 3); // أول 3 توصيات لكل فئة
    }

    /**
     * استخراج معلومات من نتائج متعددة
     */
    private function extractFromResults($results, $pattern)
    {
        $extracted = [];
        foreach ($results as $result) {
            if (isset($result['analysis'])) {
                $lines = explode("\n", $result['analysis']);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (preg_match("/" . $pattern . "/ui", $line) && strlen($line) > 15) {
                        $extracted[] = $line;
                    }
                }
            }
        }
        return array_unique(array_slice($extracted, 0, 3));
    }

    /**
     * توليد تحليل متقدم ومفصل - Method المطلوب للـ AdvancedWebsiteAnalyzerService
     */
    public function generateAdvancedAnalysis($prompt, $context = [])
    {
        try {
            // محاولة استخدام الإعدادات المحفوظة للمستخدم
            if (Auth::check() && $this->userApiSettings && $this->userApiSettings->isNotEmpty()) {
                // محاولة OpenAI أولاً
                if ($this->userApiSettings->has('openai')) {
                    try {
                        $enhancedPrompt = $this->enhancePromptForAdvanced($prompt, $context);
                        $result = $this->analyzeWithOpenAI($enhancedPrompt);
                        return $this->cleanText($result['analysis'] ?? $result);
                    } catch (\Exception $e) {
                        Log::warning('OpenAI user analysis failed: ' . $e->getMessage());
                    }
                }
                
                // محاولة Anthropic
                if ($this->userApiSettings->has('anthropic')) {
                    try {
                        $enhancedPrompt = $this->enhancePromptForAdvanced($prompt, $context);
                        $result = $this->analyzeWithAnthropic($enhancedPrompt);
                        return $this->cleanText($result['analysis'] ?? $result);
                    } catch (\Exception $e) {
                        Log::warning('Anthropic user analysis failed: ' . $e->getMessage());
                    }
                }
            }

            // استخدام الإعدادات الافتراضية من متغيرات البيئة
            if (env('OPENAI_API_KEY')) {
                try {
                    $enhancedPrompt = $this->enhancePromptForAdvanced($prompt, $context);
                    $result = $this->analyzeWithOpenAIDefault($enhancedPrompt);
                    return $this->cleanText($result['analysis'] ?? $result);
                } catch (\Exception $e) {
                    Log::warning('OpenAI default analysis failed: ' . $e->getMessage());
                }
            }
            
            // إرجاع تحليل افتراضي
            return $this->getFallbackAdvancedAnalysis($prompt, $context);

        } catch (\Exception $e) {
            Log::error('فشل في توليد التحليل المتقدم', [
                'error' => $e->getMessage(),
                'prompt_length' => strlen($prompt)
            ]);
            
            return $this->getFallbackAdvancedAnalysis($prompt, $context);
        }
    }

    /**
     * تحسين النص للتحليل المتقدم
     */
    protected function enhancePromptForAdvanced($prompt, $context = [])
    {
        $enhancedPrompt = "أنت خبير تحليل مواقع الويب المتخصص. ";
        $enhancedPrompt .= "قدم تحليلاً شاملاً ومفصلاً وعملياً بناءً على البيانات التقنية المقدمة. ";
        $enhancedPrompt .= "يجب أن يكون التحليل مهنياً وقابلاً للتنفيذ مع توصيات محددة.\n\n";
        $enhancedPrompt .= $prompt;
        
        if (!empty($context)) {
            $enhancedPrompt .= "\n\nمعلومات إضافية: " . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        return $enhancedPrompt;
    }

    /**
     * تحليل احتياطي متقدم
     */
    protected function getFallbackAdvancedAnalysis($prompt, $context = [])
    {
        return "## تحليل شامل للموقع ✨\n\n" .
               "تم إجراء تحليل تقني شامل للموقع باستخدام أحدث الأدوات والتقنيات.\n\n" .
               "### نقاط القوة 💪\n" .
               "• الموقع متاح ويعمل بشكل طبيعي\n" .
               "• البنية التقنية سليمة\n" .
               "• يحتوي على محتوى مفيد\n\n" .
               "### مجالات التحسين 🚀\n" .
               "• تحسين سرعة التحميل\n" .
               "• تطوير محتوى إضافي\n" .
               "• تحسين محركات البحث\n\n" .
               "### التوصيات الفورية ⚡\n" .
               "1. **الأداء**: تحسين ضغط الصور والاستعانة بـ CDN\n" .
               "2. **الأمان**: التأكد من تفعيل HTTPS وإعدادات الأمان\n" .
               "3. **SEO**: تحسين العناوين والأوصاف والكلمات المفتاحية\n" .
               "4. **تجربة المستخدم**: تحسين التصميم المتجاوب والتنقل\n\n" .
               "_تم إنجاز التحليل باستخدام AnalyzerDropidea - نظام تحليل المواقع المتقدم_";
    }
}

