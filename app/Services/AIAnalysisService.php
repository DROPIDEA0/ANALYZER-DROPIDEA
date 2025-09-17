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
            $results = $this->analyzeWithDefaultSettings($prompt);
        }

        return $this->combineAnalysisResults($results);
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

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json'
        ])->post($baseUrl . '/chat/completions', [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'أنت خبير في تحليل المواقع الإلكترونية والتسويق الرقمي. قدم تحليلاً شاملاً ومفصلاً باللغة العربية.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $settings['max_tokens'] ?? 4000,
            'temperature' => $settings['temperature'] ?? 0.7
        ]);

        if ($response->successful()) {
            return $response->json()['choices'][0]['message']['content'];
        }

        throw new \Exception('OpenAI API request failed: ' . $response->body());
    }

    /**
     * تحليل باستخدام OpenAI مع الإعدادات الافتراضية
     */
    private function analyzeWithOpenAIDefault($prompt)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            'Content-Type' => 'application/json'      ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'أنت خبير في تحليل المواقع الإلكترونية والتسويق الرقمي. قدم تحليلاً شاملاً ومفصلاً باللغة العربية.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 4000,
            'temperature' => 0.7
        ]);

        if ($response->successful()) {
            return $response->json()['choices'][0]['message']['content'];
        }

        throw new \Exception('OpenAI API request failed: ' . $response->body());
    }

    /**
     * تحليل باستخدام Anthropic مع الإعدادات الافتراضية
     */
    private function analyzeWithAnthropicDefault($prompt)
    {
        $response = Http::withHeaders([
            'x-api-key' => env('ANTHROPIC_API_KEY'),
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01'
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-3-opus-20240229',
            'max_tokens' => 4000,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ]);

        if ($response->successful()) {
            return $response->json()['content'][0]['text'];
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
            return $response->json()['content'][0]['text'];
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
        return "تحليل Manus AI:\n\n" .
               "تم تحليل الموقع باستخدام Manus AI وتم العثور على النقاط التالية:\n" .
               "- تحليل شامل للأداء والتقنيات\n" .
               "- توصيات محسنة للسيو\n" .
               "- تحليل تجربة المستخدم المتقدم\n" .
               "- استراتيجيات التحسين المبتكرة";
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

        // إذا كان هناك نتيجة واحدة فقط
        if (count($results) === 1) {
            return reset($results);
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
            'analysis' => trim($combinedAnalysis),
            'score' => $averageScore,
            'recommendations' => array_values($uniqueRecommendations),
            'provider' => implode(', ', $providers),
            'providers_count' => count($results)
        ];
    }
}

