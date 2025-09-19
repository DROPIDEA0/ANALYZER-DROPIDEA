<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <title>تقرير تحليل {{ $is_business ? $data['business_name'] ?? 'العمل التجاري' : 'الموقع الإلكتروني' }}</title>
    <style>
        /* IBM Plex Sans Arabic - استخدام الخط الافتراضي المدعوم في dompdf */
        /* سيتم استخدام DejaVu Sans لدعم أفضل للعربية */
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'IBM Plex Sans Arabic', 'DejaVu Sans', sans-serif;
            line-height: 1.6;
            color: #333;
            direction: rtl;
            text-align: right;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .header .date {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .business-info {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .business-info h2 {
            color: #495057;
            font-size: 20px;
            margin-bottom: 15px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: 600;
            padding: 8px 20px 8px 0;
            width: 30%;
            color: #6c757d;
        }
        
        .info-value {
            display: table-cell;
            padding: 8px 0;
        }
        
        .score-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        
        .score-circle {
            display: inline-block;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: 700;
            color: white;
        }
        
        .score-excellent { background-color: #28a745; }
        .score-good { background-color: #ffc107; color: #212529 !important; }
        .score-poor { background-color: #dc3545; }
        
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 15px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }
        
        .criteria-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        
        .criteria-item {
            display: table-row;
            border-bottom: 1px solid #e9ecef;
        }
        
        .criteria-name {
            display: table-cell;
            padding: 12px 20px 12px 0;
            font-weight: 500;
            width: 40%;
        }
        
        .criteria-status {
            display: table-cell;
            padding: 12px 20px 12px 0;
            width: 20%;
        }
        
        .criteria-desc {
            display: table-cell;
            padding: 12px 0;
            color: #6c757d;
            font-size: 14px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-yes {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-no {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .recommendations {
            background: #e7f3ff;
            border: 1px solid #b3d7ff;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .recommendations h3 {
            color: #0056b3;
            margin-bottom: 15px;
        }
        
        .recommendations ol {
            padding-right: 20px;
        }
        
        .recommendations li {
            margin-bottom: 8px;
            line-height: 1.5;
        }
        
        .strengths-weaknesses {
            display: table;
            width: 100%;
            margin-top: 20px;
        }
        
        .strengths, .weaknesses {
            display: table-cell;
            width: 48%;
            padding: 15px;
            border-radius: 8px;
            margin: 0 1%;
        }
        
        .strengths {
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }
        
        .weaknesses {
            background: #f8d7da;
            border: 1px solid #f1b0b7;
        }
        
        .strengths h4 {
            color: #155724;
            margin-bottom: 10px;
        }
        
        .weaknesses h4 {
            color: #721c24;
            margin-bottom: 10px;
        }
        
        .strengths ul, .weaknesses ul {
            padding-right: 20px;
        }
        
        .strengths li, .weaknesses li {
            margin-bottom: 5px;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
        }
        
        @page {
            margin: 20mm;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>تقرير تحليل {{ $is_business ? 'العمل التجاري' : 'الموقع الإلكتروني' }}</h1>
        <div class="date">تاريخ التحليل: {{ $generated_at }}</div>
    </div>

    @if($is_business)
    <div class="business-info">
        <h2>معلومات العمل التجاري</h2>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">اسم العمل:</div>
                <div class="info-value">{{ $data['business_name'] ?? 'غير محدد' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">التقييم:</div>
                <div class="info-value">
                    @if(isset($data['gmb_data']['rating']) && $data['gmb_data']['rating'] > 0)
                        {{ number_format($data['gmb_data']['rating'], 1) }} / 5 ⭐
                    @else
                        غير متوفر
                    @endif
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">العنوان:</div>
                <div class="info-value">{{ $data['gmb_data']['address'] ?? 'غير محدد' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">رقم الهاتف:</div>
                <div class="info-value">{{ $data['gmb_data']['phone'] ?? 'غير متوفر' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">الموقع الإلكتروني:</div>
                <div class="info-value">{{ $data['gmb_data']['website'] ?? 'غير متوفر' }}</div>
            </div>
        </div>
    </div>
    @endif

    <div class="score-section">
        <h2>النتيجة الإجمالية</h2>
        @php
            $score = $data['overall_score'] ?? 0;
            $scoreClass = $score >= 80 ? 'score-excellent' : ($score >= 60 ? 'score-good' : 'score-poor');
            $scoreText = $score >= 80 ? 'ممتاز' : ($score >= 60 ? 'جيد' : 'يحتاج تحسين');
        @endphp
        <div class="score-circle {{ $scoreClass }}">
            {{ $score }}%
        </div>
        <p><strong>التقييم: {{ $scoreText }}</strong></p>
        <p>
            @if($score >= 80)
                أداء ممتاز! العمل يتميز في جميع المعايير المهمة.
            @elseif($score >= 60)
                أداء جيد مع وجود فرص للتحسين في بعض المجالات.
            @else
                يحتاج العمل إلى تحسينات جوهرية في عدة مجالات لتحسين الأداء.
            @endif
        </p>
    </div>

    @if($is_business)
    <div class="section">
        <h3 class="section-title">تفاصيل التقييم</h3>
        <div class="criteria-grid">
            <div class="criteria-item">
                <div class="criteria-name">الحجز المباشر</div>
                <div class="criteria-status">
                    <span class="status-badge status-{{ !empty($data['gmb_data']['website']) ? 'yes' : 'no' }}">
                        {{ !empty($data['gmb_data']['website']) ? 'نعم' : 'لا' }}
                    </span>
                </div>
                <div class="criteria-desc">
                    {{ !empty($data['gmb_data']['website']) ? 'متوفر موقع إلكتروني للحجز' : 'لا يوجد موقع إلكتروني' }}
                </div>
            </div>
            
            <div class="criteria-item">
                <div class="criteria-name">الموقع الإلكتروني</div>
                <div class="criteria-status">
                    <span class="status-badge status-{{ !empty($data['gmb_data']['website']) ? 'yes' : 'no' }}">
                        {{ !empty($data['gmb_data']['website']) ? 'نعم' : 'لا' }}
                    </span>
                </div>
                <div class="criteria-desc">
                    {{ !empty($data['gmb_data']['website']) ? 'موقع إلكتروني متوفر' : 'يحتاج إنشاء موقع إلكتروني' }}
                </div>
            </div>

            <div class="criteria-item">
                <div class="criteria-name">هل عندك صفحة جوجل ماب؟</div>
                <div class="criteria-status">
                    <span class="status-badge status-yes">نعم</span>
                </div>
                <div class="criteria-desc">صفحة جوجل ماب متوفرة ونشطة</div>
            </div>

            <div class="criteria-item">
                <div class="criteria-name">عدد التقييمات ومتوسط النجوم</div>
                <div class="criteria-status">
                    <span class="status-badge status-{{ (($data['gmb_data']['rating'] ?? 0) >= 4.0) ? 'yes' : 'no' }}">
                        {{ (($data['gmb_data']['rating'] ?? 0) >= 4.0) ? 'ممتاز' : 'جيد' }}
                    </span>
                </div>
                <div class="criteria-desc">
                    التقييم: {{ number_format($data['gmb_data']['rating'] ?? 0, 1) }} / 5
                </div>
            </div>

            <div class="criteria-item">
                <div class="criteria-name">ساعات العمل</div>
                <div class="criteria-status">
                    <span class="status-badge status-{{ ($data['gmb_data']['business_hours'] ?? 'غير متوفرة') !== 'غير متوفرة' ? 'yes' : 'no' }}">
                        {{ ($data['gmb_data']['business_hours'] ?? 'غير متوفرة') !== 'غير متوفرة' ? 'متوفرة' : 'غير متوفرة' }}
                    </span>
                </div>
                <div class="criteria-desc">
                    {{ ($data['gmb_data']['business_hours'] ?? 'غير متوفرة') !== 'غير متوفرة' ? 'ساعات العمل محدثة' : 'يحتاج تحديث ساعات العمل' }}
                </div>
            </div>

            <div class="criteria-item">
                <div class="criteria-name">رقم اتصال</div>
                <div class="criteria-status">
                    <span class="status-badge status-{{ !empty($data['gmb_data']['phone']) ? 'yes' : 'no' }}">
                        {{ !empty($data['gmb_data']['phone']) ? 'متوفر' : 'غير متوفر' }}
                    </span>
                </div>
                <div class="criteria-desc">
                    {{ !empty($data['gmb_data']['phone']) ? 'رقم هاتف محدث ومتاح' : 'يحتاج إضافة رقم هاتف' }}
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="recommendations">
        <h3>التوصيات الاستراتيجية</h3>
        <ol>
            @php
                $recommendations = [
                    'تحسين جودة الخدمة للحصول على تقييمات أفضل',
                    'تشجيع العملاء على ترك مراجعات إيجابية',
                    'تحديث المعلومات في Google My Business بانتظام',
                    'إضافة صور عالية الجودة للعمل التجاري',
                    'الرد على جميع مراجعات العملاء بطريقة احترافية',
                    'تحديث ساعات العمل في المواسم المختلفة',
                    'إضافة وصف مفصل عن الخدمات المقدمة'
                ];
            @endphp
            @foreach($recommendations as $recommendation)
                <li>{{ $recommendation }}</li>
            @endforeach
        </ol>
    </div>

    <div class="strengths-weaknesses">
        <div class="strengths">
            <h4>نقاط القوة</h4>
            <ul>
                @if(!empty($data['gmb_data']['rating']) && $data['gmb_data']['rating'] >= 4.0)
                    <li>تقييم عالي من العملاء</li>
                @endif
                @if(!empty($data['gmb_data']['website']))
                    <li>وجود موقع إلكتروني</li>
                @endif
                @if(!empty($data['gmb_data']['phone']))
                    <li>رقم هاتف متاح للتواصل</li>
                @endif
                @if(($data['gmb_data']['business_hours'] ?? 'غير متوفرة') !== 'غير متوفرة')
                    <li>ساعات العمل محددة ومعلنة</li>
                @endif
                <li>وجود على خرائط جوجل</li>
            </ul>
        </div>
        
        <div class="weaknesses">
            <h4>نقاط التحسين</h4>
            <ul>
                @if(empty($data['gmb_data']['rating']) || $data['gmb_data']['rating'] < 4.0)
                    <li>تحسين جودة الخدمة والتقييمات</li>
                @endif
                @if(empty($data['gmb_data']['website']))
                    <li>إنشاء موقع إلكتروني</li>
                @endif
                @if(empty($data['gmb_data']['phone']))
                    <li>إضافة رقم هاتف للتواصل</li>
                @endif
                @if(($data['gmb_data']['business_hours'] ?? 'غير متوفرة') === 'غير متوفرة')
                    <li>تحديد ساعات العمل</li>
                @endif
                @if(($data['gmb_data']['reviews_count'] ?? 0) < 10)
                    <li>زيادة عدد المراجعات</li>
                @endif
            </ul>
        </div>
    </div>

    <div class="footer">
        <p>تم إنشاء هذا التقرير بواسطة AnalyzerDropidea - منصة تحليل الأعمال التجارية</p>
        <p>© {{ date('Y') }} جميع الحقوق محفوظة</p>
    </div>
</body>
</html>