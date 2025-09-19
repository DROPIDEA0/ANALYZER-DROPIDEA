<?php

namespace App\Services;

use App\Models\WebsiteAnalysis;
use Illuminate\Support\Facades\Log;

/**
 * خدمة التقرير الموحد - Phase 2
 * 
 * تقوم بتوحيد جميع نتائج التحليلات في تقرير شامل يتضمن:
 * - Executive Summary
 * - Website Health Card  
 * - Google Maps Entity Card
 * - نقاط القوة والضعف
 * - التوصيات القابلة للتنفيذ
 * - مقارنة مع المعدل الصناعي
 */
class UnifiedReportService
{
    /**
     * إنشاء التقرير الموحد
     */
    public function generateUnifiedReport($analysisData, $analysisId = null)
    {
        try {
            $report = [
                'analysis_id' => $analysisId,
                'generated_at' => now(),
                'executive_summary' => $this->generateExecutiveSummary($analysisData),
                'website_health_card' => $this->generateWebsiteHealthCard($analysisData),
                'google_maps_entity_card' => $this->generateGoogleMapsCard($analysisData),
                'strengths_weaknesses' => $this->analyzeStrengthsWeaknesses($analysisData),
                'actionable_recommendations' => $this->generateActionableRecommendations($analysisData),
                'priority_issues' => $this->identifyPriorityIssues($analysisData),
                'industry_comparison' => $this->compareWithIndustryAverage($analysisData),
                'composite_score' => $this->calculateCompositeScore($analysisData)
            ];

            return $report;

        } catch (\Exception $e) {
            Log::error('Unified Report Generation Error', [
                'analysis_id' => $analysisId,
                'error' => $e->getMessage()
            ]);
            
            return $this->generateFallbackReport($analysisData);
        }
    }

    /**
     * إنشاء الملخص التنفيذي
     */
    protected function generateExecutiveSummary($analysisData)
    {
        $overallScore = $analysisData['overall_score'] ?? 70;
        $url = $analysisData['url'] ?? 'الموقع';
        
        $performance = $this->getPerformanceLevel($overallScore);
        
        return [
            'overall_assessment' => $performance['assessment'],
            'key_findings' => $performance['findings'],
            'main_score' => $overallScore,
            'url' => $url,
            'analysis_date' => now()->format('Y-m-d'),
            'summary_text' => "تم تحليل موقع {$url} وحصل على نتيجة إجمالية {$overallScore}% مما يعني أداء {$performance['level']}. {$performance['recommendation']}"
        ];
    }

    /**
     * إنشاء بطاقة صحة الموقع
     */
    protected function generateWebsiteHealthCard($analysisData)
    {
        return [
            'overall_health' => $this->determineHealthLevel($analysisData['overall_score'] ?? 70),
            'metrics' => [
                'seo_health' => [
                    'score' => $analysisData['seo_score'] ?? 0,
                    'status' => $this->getHealthStatus($analysisData['seo_score'] ?? 0),
                    'icon' => '🔍'
                ],
                'performance_health' => [
                    'score' => $analysisData['performance_score'] ?? 0,
                    'status' => $this->getHealthStatus($analysisData['performance_score'] ?? 0),
                    'icon' => '⚡'
                ],
                'security_health' => [
                    'score' => $analysisData['security_score'] ?? 75,
                    'status' => $this->getHealthStatus($analysisData['security_score'] ?? 75),
                    'icon' => '🔒'
                ],
                'ux_health' => [
                    'score' => $analysisData['ux_score'] ?? 70,
                    'status' => $this->getHealthStatus($analysisData['ux_score'] ?? 70),
                    'icon' => '👥'
                ]
            ],
            'health_indicators' => $this->generateHealthIndicators($analysisData)
        ];
    }

    /**
     * إنشاء بطاقة Google Maps Entity
     */
    protected function generateGoogleMapsCard($analysisData)
    {
        $gmbData = $analysisData['gmb_data'] ?? [];
        
        return [
            'has_gmb_presence' => !empty($gmbData),
            'business_name' => $gmbData['name'] ?? ($analysisData['business_name'] ?? 'غير محدد'),
            'address' => $gmbData['address'] ?? 'العنوان غير متوفر',
            'rating' => $gmbData['rating'] ?? 0,
            'reviews_count' => $gmbData['reviews_count'] ?? 0,
            'verification_status' => $gmbData['is_verified'] ?? false,
            'business_hours' => $gmbData['business_hours'] ?? null,
            'photos_count' => count($gmbData['photos'] ?? []),
            'categories' => $gmbData['categories'] ?? [],
            'gmb_optimization_score' => $this->calculateGmbOptimizationScore($gmbData)
        ];
    }

    /**
     * تحليل نقاط القوة والضعف
     */
    protected function analyzeStrengthsWeaknesses($analysisData)
    {
        $strengths = [];
        $weaknesses = [];

        // تحليل نقاط القوة
        if (($analysisData['seo_score'] ?? 0) >= 75) {
            $strengths[] = 'تحسين ممتاز لمحركات البحث (SEO)';
        }
        
        if (($analysisData['performance_score'] ?? 0) >= 75) {
            $strengths[] = 'أداء سريع للموقع وزمن تحميل منخفض';
        }
        
        if (($analysisData['security_score'] ?? 0) >= 75) {
            $strengths[] = 'مستوى أمان عالي مع شهادات SSL صالحة';
        }

        // تحليل نقاط الضعف
        if (($analysisData['seo_score'] ?? 0) < 60) {
            $weaknesses[] = 'يحتاج تحسين كبير في SEO والميتاداتا';
        }
        
        if (($analysisData['performance_score'] ?? 0) < 60) {
            $weaknesses[] = 'سرعة التحميل بطيئة تؤثر على تجربة المستخدم';
        }
        
        if (empty($analysisData['gmb_data'])) {
            $weaknesses[] = 'لا توجد بيانات Google My Business';
        }

        return [
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
            'strengths_count' => count($strengths),
            'weaknesses_count' => count($weaknesses)
        ];
    }

    /**
     * إنشاء التوصيات القابلة للتنفيذ
     */
    protected function generateActionableRecommendations($analysisData)
    {
        $recommendations = [
            'high_priority' => [],
            'medium_priority' => [],
            'low_priority' => []
        ];

        // توصيات عالية الأولوية
        if (($analysisData['seo_score'] ?? 0) < 50) {
            $recommendations['high_priority'][] = [
                'title' => 'تحسين العناوين والوصف',
                'description' => 'إضافة عناوين H1 مناسبة ومعلومات Meta Description',
                'impact' => 'عالي',
                'effort' => 'منخفض',
                'category' => 'SEO'
            ];
        }

        if (($analysisData['performance_score'] ?? 0) < 50) {
            $recommendations['high_priority'][] = [
                'title' => 'ضغط الصور وتحسين السرعة',
                'description' => 'تصغير حجم الصور واستخدام تقنيات الضغط الحديثة',
                'impact' => 'عالي',
                'effort' => 'متوسط',
                'category' => 'الأداء'
            ];
        }

        // توصيات متوسطة الأولوية
        if (empty($analysisData['gmb_data'])) {
            $recommendations['medium_priority'][] = [
                'title' => 'إنشاء ملف Google My Business',
                'description' => 'تسجيل العمل في Google My Business لتحسين الظهور المحلي',
                'impact' => 'متوسط',
                'effort' => 'منخفض',
                'category' => 'التسويق المحلي'
            ];
        }

        return $recommendations;
    }

    /**
     * تحديد المسائل ذات الأولوية
     */
    protected function identifyPriorityIssues($analysisData)
    {
        $issues = [];
        
        if (($analysisData['overall_score'] ?? 0) < 40) {
            $issues[] = [
                'severity' => 'critical',
                'title' => 'نتيجة إجمالية منخفضة جداً',
                'description' => 'الموقع يحتاج تحسينات شاملة في جميع المجالات'
            ];
        }

        if (($analysisData['security_score'] ?? 0) < 60) {
            $issues[] = [
                'severity' => 'high',
                'title' => 'مشاكل أمنية',
                'description' => 'يجب تحسين الأمان وإضافة شهادات SSL'
            ];
        }

        return $issues;
    }

    /**
     * مقارنة مع المعدل الصناعي
     */
    protected function compareWithIndustryAverage($analysisData)
    {
        // معدلات افتراضية للمقارنة
        $industryAverages = [
            'overall_score' => 65,
            'seo_score' => 60,
            'performance_score' => 70,
            'security_score' => 75,
            'ux_score' => 68
        ];

        $comparison = [];
        foreach ($industryAverages as $metric => $average) {
            $userScore = $analysisData[$metric] ?? 0;
            $comparison[$metric] = [
                'user_score' => $userScore,
                'industry_average' => $average,
                'difference' => $userScore - $average,
                'performance' => $userScore >= $average ? 'above_average' : 'below_average'
            ];
        }

        return $comparison;
    }

    /**
     * حساب النتيجة المركبة (Composite Score)
     */
    protected function calculateCompositeScore($analysisData)
    {
        $weights = [
            'seo_score' => 0.30,        // 30%
            'performance_score' => 0.25, // 25%
            'security_score' => 0.15,    // 15%
            'ux_score' => 0.15,         // 15%
            'gmb_presence' => 0.15      // 15%
        ];

        $totalScore = 0;
        $totalWeight = 0;

        foreach ($weights as $metric => $weight) {
            if ($metric === 'gmb_presence') {
                $score = !empty($analysisData['gmb_data']) ? 85 : 30;
            } else {
                $score = $analysisData[$metric] ?? 0;
            }
            
            $totalScore += $score * $weight;
            $totalWeight += $weight;
        }

        return round($totalScore / $totalWeight);
    }

    /**
     * مساعد - تحديد مستوى الأداء
     */
    protected function getPerformanceLevel($score)
    {
        if ($score >= 85) {
            return [
                'level' => 'ممتاز',
                'assessment' => 'ممتاز',
                'findings' => ['أداء عالي في جميع المقاييس', 'موقع محسّن بشكل جيد'],
                'recommendation' => 'حافظ على هذا المستوى الممتاز.'
            ];
        } elseif ($score >= 70) {
            return [
                'level' => 'جيد',
                'assessment' => 'جيد',
                'findings' => ['أداء جيد مع مجال للتحسين', 'بعض النقاط تحتاج انتباه'],
                'recommendation' => 'يمكن تحسينه أكثر مع بعض التعديلات.'
            ];
        } elseif ($score >= 50) {
            return [
                'level' => 'متوسط',
                'assessment' => 'متوسط',
                'findings' => ['أداء متوسط يحتاج تحسين', 'عدة مجالات تحتاج عمل'],
                'recommendation' => 'يحتاج تحسينات في عدة مجالات.'
            ];
        } else {
            return [
                'level' => 'ضعيف',
                'assessment' => 'ضعيف',
                'findings' => ['أداء ضعيف في أغلب المقاييس', 'يحتاج تحسينات شاملة'],
                'recommendation' => 'يحتاج إعادة تطوير شاملة.'
            ];
        }
    }

    /**
     * مساعد - تحديد حالة الصحة
     */
    protected function getHealthStatus($score)
    {
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'fair';
        return 'poor';
    }

    /**
     * مساعد - تحديد مستوى الصحة
     */
    protected function determineHealthLevel($score)
    {
        return [
            'level' => $this->getHealthStatus($score),
            'score' => $score,
            'description' => $this->getHealthDescription($score)
        ];
    }

    /**
     * مساعد - وصف مستوى الصحة
     */
    protected function getHealthDescription($score)
    {
        if ($score >= 80) return 'موقع في حالة ممتازة';
        if ($score >= 60) return 'موقع في حالة جيدة';
        if ($score >= 40) return 'موقع يحتاج تحسينات';
        return 'موقع يحتاج تدخل عاجل';
    }

    /**
     * مساعد - مؤشرات الصحة
     */
    protected function generateHealthIndicators($analysisData)
    {
        $indicators = [];
        
        // SSL Certificate
        $indicators['ssl_status'] = ($analysisData['security_score'] ?? 0) > 70 ? 'active' : 'warning';
        
        // Page Speed
        $loadTime = $analysisData['load_time'] ?? 3.0;
        $indicators['speed_status'] = $loadTime <= 2 ? 'fast' : ($loadTime <= 4 ? 'average' : 'slow');
        
        // Mobile Friendly
        $indicators['mobile_friendly'] = ($analysisData['ux_score'] ?? 0) > 60 ? 'yes' : 'needs_improvement';
        
        return $indicators;
    }

    /**
     * مساعد - حساب نقاط تحسين GMB
     */
    protected function calculateGmbOptimizationScore($gmbData)
    {
        if (empty($gmbData)) return 0;
        
        $score = 0;
        
        // التحقق من النشاط
        if ($gmbData['is_verified'] ?? false) $score += 20;
        
        // التقييمات
        if (($gmbData['rating'] ?? 0) >= 4.0) $score += 20;
        if (($gmbData['reviews_count'] ?? 0) > 10) $score += 15;
        
        // الصور
        if (count($gmbData['photos'] ?? []) >= 5) $score += 15;
        
        // ساعات العمل
        if (!empty($gmbData['business_hours'])) $score += 10;
        
        // الفئات
        if (!empty($gmbData['categories'])) $score += 10;
        
        // معلومات الاتصال
        if (!empty($gmbData['phone']) && !empty($gmbData['website'])) $score += 10;
        
        return min($score, 100);
    }

    /**
     * تقرير احتياطي في حالة الفشل
     */
    protected function generateFallbackReport($analysisData)
    {
        return [
            'executive_summary' => [
                'overall_assessment' => 'متوسط',
                'main_score' => $analysisData['overall_score'] ?? 70,
                'summary_text' => 'تم إجراء تحليل أساسي للموقع'
            ],
            'website_health_card' => [
                'overall_health' => ['level' => 'good', 'score' => 70]
            ],
            'strengths_weaknesses' => [
                'strengths' => ['تم تحليل الموقع بنجاح'],
                'weaknesses' => ['بحاجة لمزيد من البيانات']
            ]
        ];
    }
}