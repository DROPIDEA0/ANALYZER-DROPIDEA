<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class ReportGeneratorService
{
    /**
     * إنشاء تقرير PDF محسن مع الذكاء الاصطناعي
     */
    public function generateEnhancedPDFReport($analysisData)
    {
        try {
            // إعداد البيانات للتقرير
            $reportData = $this->prepareEnhancedReportData($analysisData);
            
            // إنشاء HTML للتقرير
            $html = $this->generateEnhancedReportHTML($reportData);
            
            // تحويل إلى PDF
            $pdf = PDF::loadHTML($html);
            
            // إعداد خصائص PDF
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => true,
            ]);
            
            return $pdf->output();
            
        } catch (\Exception $e) {
            throw new \Exception('خطأ في إنشاء تقرير PDF: ' . $e->getMessage());
        }
    }

    /**
     * إعداد بيانات التقرير المحسن
     */
    private function prepareEnhancedReportData($analysisData)
    {
        $reportData = [
            'title' => 'تقرير تحليل موقع الويب الشامل بالذكاء الاصطناعي',
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'url' => $analysisData['url'] ?? '',
            'region' => $this->getRegionName($analysisData['region'] ?? 'global'),
            'analysis_type' => $this->getAnalysisTypeName($analysisData['analysis_type'] ?? 'full'),
        ];

        // معلومات أساسية
        if (isset($analysisData['basic_info'])) {
            $reportData['basic_info'] = $analysisData['basic_info'];
        }

        // تحليل التقنيات
        if (isset($analysisData['technologies'])) {
            $reportData['technologies'] = $analysisData['technologies'];
        }

        // تحليل السيو
        if (isset($analysisData['seo_analysis'])) {
            $reportData['seo_analysis'] = $analysisData['seo_analysis'];
        }

        // تحليل الأداء
        if (isset($analysisData['performance_analysis'])) {
            $reportData['performance_analysis'] = $analysisData['performance_analysis'];
        }

        // تحليل المنافسين
        if (isset($analysisData['competitor_analysis'])) {
            $reportData['competitor_analysis'] = $analysisData['competitor_analysis'];
        }

        // تحليل الذكاء الاصطناعي
        if (isset($analysisData['ai_analysis'])) {
            $reportData['ai_analysis'] = $analysisData['ai_analysis'];
        }

        // النقاط والملخص
        $reportData['scores'] = [
            'seo_score' => $analysisData['seo_score'] ?? 0,
            'performance_score' => $analysisData['performance_score'] ?? 0,
            'ai_score' => $analysisData['ai_analysis']['overall_score'] ?? 0,
            'load_time' => $analysisData['load_time'] ?? 0,
        ];

        return $reportData;
    }

    /**
     * إنشاء HTML للتقرير المحسن
     */
    private function generateEnhancedReportHTML($reportData)
    {
        $html = '<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $reportData['title'] . '</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@300;400;500;600;700&display=swap");
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Noto Sans Arabic", "DejaVu Sans", sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #fff;
            direction: rtl;
            font-size: 14px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px 0;
            border-bottom: 3px solid #3b82f6;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .header .subtitle {
            font-size: 14px;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        .meta-info {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
        }
        
        .meta-info table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .meta-info td {
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .meta-info td:first-child {
            font-weight: 600;
            color: #374151;
            width: 30%;
        }
        
        .section {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 20px;
            color: #1e40af;
            margin-bottom: 20px;
            padding: 15px;
            background: linear-gradient(135deg, #e0f2fe 0%, #b3e5fc 100%);
            border-radius: 8px;
            border-right: 4px solid #1e40af;
            font-weight: 600;
        }
        
        .subsection {
            margin-bottom: 25px;
        }
        
        .subsection-title {
            font-size: 16px;
            color: #374151;
            margin-bottom: 15px;
            font-weight: 600;
            padding: 10px;
            background-color: #f8fafc;
            border-radius: 6px;
        }
        
        .scores-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .score-card {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .score-number {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .score-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .ai-section {
            background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #d8b4fe;
            margin-bottom: 20px;
        }
        
        .ai-title {
            color: #7c3aed;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .ai-title::before {
            content: "🤖";
            margin-left: 10px;
        }
        
        .tech-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .tech-category {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border-right: 4px solid #3b82f6;
        }
        
        .tech-category-title {
            font-weight: 600;
            color: #374151;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .tech-list {
            list-style: none;
        }
        
        .tech-list li {
            background-color: #e0f2fe;
            color: #0369a1;
            padding: 4px 8px;
            margin: 4px 0;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
            margin-left: 5px;
        }
        
        .recommendations-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .recommendation-category {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            border-right: 4px solid #10b981;
        }
        
        .recommendation-category.seo {
            border-right-color: #3b82f6;
            background-color: #eff6ff;
        }
        
        .recommendation-category.performance {
            border-right-color: #10b981;
            background-color: #f0fdf4;
        }
        
        .recommendation-category.security {
            border-right-color: #ef4444;
            background-color: #fef2f2;
        }
        
        .recommendation-category.ux {
            border-right-color: #8b5cf6;
            background-color: #faf5ff;
        }
        
        .recommendation-title {
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .recommendation-list {
            list-style: none;
        }
        
        .recommendation-list li {
            padding: 5px 0;
            font-size: 13px;
            line-height: 1.5;
        }
        
        .recommendation-list li::before {
            content: "💡";
            margin-left: 8px;
        }
        
        .strengths-weaknesses {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .strengths {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            padding: 15px;
            border-radius: 8px;
            border-right: 4px solid #10b981;
        }
        
        .weaknesses {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            padding: 15px;
            border-radius: 8px;
            border-right: 4px solid #ef4444;
        }
        
        .list-title {
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .list-title.strengths {
            color: #059669;
        }
        
        .list-title.weaknesses {
            color: #dc2626;
        }
        
        .point-list {
            list-style: none;
        }
        
        .point-list li {
            padding: 5px 0;
            font-size: 13px;
            line-height: 1.4;
        }
        
        .strengths .point-list li::before {
            content: "✅";
            margin-left: 8px;
        }
        
        .weaknesses .point-list li::before {
            content: "⚠️";
            margin-left: 8px;
        }
        
        .footer {
            margin-top: 50px;
            padding: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 8px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        @media print {
            .container {
                max-width: none;
                margin: 0;
                padding: 15px;
            }
            
            .scores-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">';

        // رأس التقرير
        $html .= $this->generateEnhancedHeader($reportData);

        // معلومات التحليل
        $html .= $this->generateEnhancedMetaInfo($reportData);

        // النقاط الإجمالية
        $html .= $this->generateScoresSection($reportData['scores']);

        // تحليل الذكاء الاصطناعي
        if (isset($reportData['ai_analysis'])) {
            $html .= $this->generateAIAnalysisSection($reportData['ai_analysis']);
        }

        // التقنيات المستخدمة
        if (isset($reportData['technologies'])) {
            $html .= $this->generateTechnologiesSection($reportData['technologies']);
        }

        // تحليل السيو
        if (isset($reportData['seo_analysis'])) {
            $html .= $this->generateEnhancedSeoSection($reportData['seo_analysis']);
        }

        // تحليل الأداء
        if (isset($reportData['performance_analysis'])) {
            $html .= $this->generateEnhancedPerformanceSection($reportData['performance_analysis']);
        }

        // التوصيات المفصلة
        if (isset($reportData['ai_analysis'])) {
            $html .= $this->generateDetailedRecommendations($reportData['ai_analysis']);
        }

        // الخاتمة
        $html .= $this->generateEnhancedFooter();

        $html .= '
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * إنشاء رأس التقرير المحسن
     */
    private function generateEnhancedHeader($reportData)
    {
        return '
        <div class="header">
            <h1>' . $reportData['title'] . '</h1>
            <div class="subtitle">تحليل شامل لموقع: ' . $reportData['url'] . '</div>
            <div class="subtitle">تاريخ التحليل: ' . $reportData['generated_at'] . '</div>
            <div class="subtitle">مدعوم بتقنيات الذكاء الاصطناعي المتقدمة</div>
        </div>';
    }

    /**
     * إنشاء معلومات التحليل المحسنة
     */
    private function generateEnhancedMetaInfo($reportData)
    {
        return '
        <div class="meta-info">
            <table>
                <tr>
                    <td>🌐 رابط الموقع:</td>
                    <td>' . $reportData['url'] . '</td>
                </tr>
                <tr>
                    <td>📍 المنطقة الجغرافية:</td>
                    <td>' . $reportData['region'] . '</td>
                </tr>
                <tr>
                    <td>🔍 نوع التحليل:</td>
                    <td>' . $reportData['analysis_type'] . '</td>
                </tr>
                <tr>
                    <td>📅 تاريخ الإنشاء:</td>
                    <td>' . $reportData['generated_at'] . '</td>
                </tr>
            </table>
        </div>';
    }

    /**
     * إنشاء قسم النقاط
     */
    private function generateScoresSection($scores)
    {
        $html = '
        <div class="section">
            <h2 class="section-title">📊 النقاط الإجمالية</h2>
            <div class="scores-grid">';

        if ($scores['seo_score'] > 0) {
            $html .= '
                <div class="score-card">
                    <div class="score-number">' . $scores['seo_score'] . '</div>
                    <div class="score-label">نقاط السيو</div>
                </div>';
        }

        if ($scores['performance_score'] > 0) {
            $html .= '
                <div class="score-card">
                    <div class="score-number">' . $scores['performance_score'] . '</div>
                    <div class="score-label">نقاط الأداء</div>
                </div>';
        }

        if ($scores['ai_score'] > 0) {
            $html .= '
                <div class="score-card">
                    <div class="score-number">' . $scores['ai_score'] . '</div>
                    <div class="score-label">تقييم الذكاء الاصطناعي</div>
                </div>';
        }

        if ($scores['load_time'] > 0) {
            $html .= '
                <div class="score-card">
                    <div class="score-number">' . number_format($scores['load_time'], 1) . 's</div>
                    <div class="score-label">وقت التحميل</div>
                </div>';
        }

        $html .= '
            </div>
        </div>';

        return $html;
    }

    /**
     * إنشاء قسم تحليل الذكاء الاصطناعي
     */
    private function generateAIAnalysisSection($aiAnalysis)
    {
        $html = '
        <div class="section page-break">
            <h2 class="section-title">🤖 تحليل الذكاء الاصطناعي</h2>';

        if (isset($aiAnalysis['summary'])) {
            $html .= '
            <div class="ai-section">
                <div class="ai-title">ملخص التحليل الذكي</div>
                <p style="line-height: 1.8; font-size: 14px;">' . nl2br(htmlspecialchars($aiAnalysis['summary'])) . '</p>
            </div>';
        }

        // نقاط القوة والضعف من الذكاء الاصطناعي
        if (isset($aiAnalysis['strengths']) || isset($aiAnalysis['weaknesses'])) {
            $html .= '
            <div class="strengths-weaknesses">';

            if (isset($aiAnalysis['strengths']) && !empty($aiAnalysis['strengths'])) {
                $html .= '
                <div class="strengths">
                    <div class="list-title strengths">نقاط القوة (تحليل ذكي)</div>
                    <ul class="point-list">';
                
                foreach (array_slice($aiAnalysis['strengths'], 0, 5) as $strength) {
                    $html .= '<li>' . htmlspecialchars($strength) . '</li>';
                }
                
                $html .= '
                    </ul>
                </div>';
            }

            if (isset($aiAnalysis['weaknesses']) && !empty($aiAnalysis['weaknesses'])) {
                $html .= '
                <div class="weaknesses">
                    <div class="list-title weaknesses">نقاط الضعف (تحليل ذكي)</div>
                    <ul class="point-list">';
                
                foreach (array_slice($aiAnalysis['weaknesses'], 0, 5) as $weakness) {
                    $html .= '<li>' . htmlspecialchars($weakness) . '</li>';
                }
                
                $html .= '
                    </ul>
                </div>';
            }

            $html .= '
            </div>';
        }

        $html .= '
        </div>';

        return $html;
    }

    /**
     * إنشاء قسم التقنيات
     */
    private function generateTechnologiesSection($technologies)
    {
        $html = '
        <div class="section">
            <h2 class="section-title">🛠️ التقنيات والبرمجيات المستخدمة</h2>
            <div class="tech-grid">';

        $categoryNames = [
            'frontend' => 'واجهة المستخدم',
            'backend' => 'الخادم الخلفي',
            'cms' => 'نظام إدارة المحتوى',
            'analytics' => 'التحليلات',
            'security' => 'الأمان',
            'hosting' => 'الاستضافة',
            'performance' => 'الأداء'
        ];

        foreach ($technologies as $category => $techs) {
            if (!empty($techs)) {
                $categoryName = $categoryNames[$category] ?? $category;
                $html .= '
                <div class="tech-category">
                    <div class="tech-category-title">' . $categoryName . '</div>
                    <ul class="tech-list">';
                
                foreach ($techs as $tech) {
                    $html .= '<li>' . htmlspecialchars($tech) . '</li>';
                }
                
                $html .= '
                    </ul>
                </div>';
            }
        }

        $html .= '
            </div>
        </div>';

        return $html;
    }

    /**
     * إنشاء قسم السيو المحسن
     */
    private function generateEnhancedSeoSection($seoAnalysis)
    {
        $html = '
        <div class="section page-break">
            <h2 class="section-title">🔍 تحليل تحسين محركات البحث (SEO)</h2>';

        if (isset($seoAnalysis['score'])) {
            $html .= '
            <div class="score-card" style="margin-bottom: 20px;">
                <div class="score-number">' . $seoAnalysis['score'] . '</div>
                <div class="score-label">نقاط السيو من 100</div>
            </div>';
        }

        // تفاصيل السيو
        $html .= '
        <div class="subsection">
            <div class="subsection-title">تفاصيل التحليل</div>
            <div style="background-color: #f8fafc; padding: 15px; border-radius: 8px;">
                <p><strong>العنوان:</strong> ' . (isset($seoAnalysis['title']) ? '✅ موجود' : '❌ غير موجود') . '</p>
                <p><strong>الوصف التعريفي:</strong> ' . (isset($seoAnalysis['has_meta_description']) && $seoAnalysis['has_meta_description'] ? '✅ موجود' : '❌ غير موجود') . '</p>
                <p><strong>الكلمات المفتاحية:</strong> ' . (isset($seoAnalysis['keywords_count']) ? $seoAnalysis['keywords_count'] . ' كلمة' : 'غير محدد') . '</p>
            </div>
        </div>';

        $html .= '
        </div>';

        return $html;
    }

    /**
     * إنشاء قسم الأداء المحسن
     */
    private function generateEnhancedPerformanceSection($performanceAnalysis)
    {
        $html = '
        <div class="section">
            <h2 class="section-title">⚡ تحليل الأداء</h2>';

        if (isset($performanceAnalysis['score'])) {
            $html .= '
            <div class="score-card" style="margin-bottom: 20px;">
                <div class="score-number">' . $performanceAnalysis['score'] . '</div>
                <div class="score-label">نقاط الأداء من 100</div>
            </div>';
        }

        // تفاصيل الأداء
        $html .= '
        <div class="subsection">
            <div class="subsection-title">مؤشرات الأداء</div>
            <div style="background-color: #f8fafc; padding: 15px; border-radius: 8px;">
                <p><strong>وقت التحميل:</strong> ' . (isset($performanceAnalysis['load_time']) ? $performanceAnalysis['load_time'] . ' ثانية' : 'غير محدد') . '</p>
                <p><strong>حجم الصفحة:</strong> ' . (isset($performanceAnalysis['page_size']) ? $performanceAnalysis['page_size'] . ' KB' : 'غير محدد') . '</p>
                <p><strong>عدد الطلبات:</strong> ' . (isset($performanceAnalysis['requests_count']) ? $performanceAnalysis['requests_count'] : 'غير محدد') . '</p>
            </div>
        </div>';

        $html .= '
        </div>';

        return $html;
    }

    /**
     * إنشاء التوصيات المفصلة
     */
    private function generateDetailedRecommendations($aiAnalysis)
    {
        $html = '
        <div class="section page-break">
            <h2 class="section-title">💡 التوصيات والتحسينات المقترحة</h2>
            <div class="recommendations-grid">';

        $recommendationTypes = [
            'seo_recommendations' => ['title' => 'تحسين محركات البحث', 'class' => 'seo'],
            'performance_recommendations' => ['title' => 'تحسين الأداء', 'class' => 'performance'],
            'security_recommendations' => ['title' => 'الأمان والحماية', 'class' => 'security'],
            'ux_recommendations' => ['title' => 'تجربة المستخدم', 'class' => 'ux'],
            'content_recommendations' => ['title' => 'المحتوى', 'class' => 'content'],
            'marketing_strategies' => ['title' => 'استراتيجيات التسويق', 'class' => 'marketing']
        ];

        foreach ($recommendationTypes as $type => $config) {
            if (isset($aiAnalysis[$type]) && !empty($aiAnalysis[$type])) {
                $html .= '
                <div class="recommendation-category ' . $config['class'] . '">
                    <div class="recommendation-title">' . $config['title'] . '</div>
                    <ul class="recommendation-list">';
                
                foreach (array_slice($aiAnalysis[$type], 0, 5) as $recommendation) {
                    $html .= '<li>' . htmlspecialchars($recommendation) . '</li>';
                }
                
                $html .= '
                    </ul>
                </div>';
            }
        }

        $html .= '
            </div>
        </div>';

        return $html;
    }

    /**
     * إنشاء الخاتمة المحسنة
     */
    private function generateEnhancedFooter()
    {
        return '
        <div class="footer">
            <p><strong>تقرير تحليل موقع الويب الشامل</strong></p>
            <p>مدعوم بتقنيات الذكاء الاصطناعي المتقدمة</p>
            <p>تم إنشاء هذا التقرير بواسطة AnalyzerDropidea - محلل المواقع الاحترافي</p>
            <p style="margin-top: 10px; font-size: 11px; color: #9ca3af;">
                هذا التقرير يحتوي على تحليل شامل ومفصل لموقعكم الإلكتروني باستخدام أحدث تقنيات الذكاء الاصطناعي
                <br>للحصول على تحليلات أكثر تفصيلاً، يرجى زيارة موقعنا الإلكتروني
            </p>
        </div>';
    }

    /**
     * الحصول على اسم المنطقة
     */
    private function getRegionName($region)
    {
        $regions = [
            'global' => 'عالمي',
            'middle-east' => 'الشرق الأوسط',
            'gulf' => 'دول الخليج',
            'egypt' => 'مصر',
            'saudi' => 'السعودية',
            'uae' => 'الإمارات'
        ];

        return $regions[$region] ?? 'غير محدد';
    }

    /**
     * الحصول على اسم نوع التحليل
     */
    private function getAnalysisTypeName($type)
    {
        $types = [
            'full' => 'تحليل شامل',
            'seo' => 'تحسين محركات البحث فقط',
            'performance' => 'الأداء فقط',
            'competitors' => 'المنافسين فقط'
        ];

        return $types[$type] ?? 'غير محدد';
    }

    /**
     * إنشاء تقرير PDF تقليدي (للتوافق مع النسخة القديمة)
     */
    public function generatePDFReport($analysisData)
    {
        return $this->generateEnhancedPDFReport($analysisData);
    }
}

