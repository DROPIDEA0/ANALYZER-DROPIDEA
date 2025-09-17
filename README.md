# برنامج تحليل مواقع الويب الشامل (Laravel + React)

هذا المشروع هو تطبيق ويب شامل لتحليل مواقع الويب، مبني باستخدام **Laravel** للواجهة الخلفية (Backend) و **React** للواجهة الأمامية (Frontend) مع **Inertia.js** لربط الواجهتين. يوفر البرنامج تقارير مفصلة عن نقاط القوة والضعف، تحليل السيو (SEO)، أداء الموقع، ومقارنة مع المنافسين، مع إمكانية تصدير التقرير كملف PDF باللغة العربية.

## الميزات الرئيسية

*   **تحليل شامل للموقع**: يقدم نظرة عامة على الموقع المستهدف.
*   **تحليل السيو (SEO)**: تقييم عناصر السيو الأساسية مثل العناوين، الوصف التعريفي، هيكل العناوين، الصور، والروابط.
*   **تحليل الأداء**: قياس سرعة التحميل، حجم الصفحة، استجابة الخادم، وتحسينات الأجهزة المحمولة.
*   **تحليل المنافسين**: تحديد المنافسين الرئيسيين، تحليل مواقعهم، ومقارنة الأداء مع الموقع المستهدف.
*   **تقارير مفصلة**: إنشاء تقارير واضحة وموجزة باللغة العربية.
*   **تصدير PDF**: إمكانية تصدير التقارير إلى ملفات PDF لسهولة المشاركة والطباعة.
*   **واجهة مستخدم حديثة**: واجهة مستخدم تفاعلية وجذابة مبنية باستخدام React و Tailwind CSS.
*   **نظام مصادقة**: تسجيل الدخول والتسجيل للمستخدمين لإدارة تحليلاتهم.

## هيكل المشروع

يتكون المشروع من جزأين رئيسيين:

1.  **الواجهة الخلفية (Backend)**: مبنية باستخدام Laravel، وتتضمن منطق العمل، إدارة قواعد البيانات، وواجهات برمجة التطبيقات (APIs) لتحليل المواقع وتوليد التقارير.
2.  **الواجهة الأمامية (Frontend)**: مبنية باستخدام React و Inertia.js، وتوفر واجهة المستخدم التفاعلية لإدخال الروابط وعرض نتائج التحليل.

```
website-analyzer-laravel/
├── app/
│   ├── Http/
│   │   └── Controllers/           # وحدات التحكم (Controllers)
│   │       └── WebsiteAnalyzerController.php
│   ├── Models/                  # نماذج قاعدة البيانات (Models)
│   │   └── WebsiteAnalysis.php
│   └── Services/                # خدمات التحليل (Analyzer Services)
│       ├── CompetitorAnalyzerService.php
│       ├── PerformanceAnalyzerService.php
│       ├── ReportGeneratorService.php
│       └── SeoAnalyzerService.php
├── bootstrap/
├── config/
├── database/
│   ├── migrations/              # ملفات الهجرة لقاعدة البيانات
│   └── database.sqlite          # قاعدة بيانات SQLite (افتراضي)
├── public/
├── resources/
│   ├── css/
│   ├── js/                      # ملفات React (الواجهة الأمامية)
│   │   ├── Components/
│   │   ├── Layouts/
│   │   ├── Pages/               # صفحات React الرئيسية
│   │   │   └── WebsiteAnalyzer.jsx
│   │   └── app.jsx
│   └── views/
├── routes/
│   └── web.php                  # مسارات الويب
├── storage/
├── tests/
├── vendor/
├── .env
├── composer.json
├── package.json
└── vite.config.js
```

## المتطلبات المسبقة

قبل البدء، تأكد من تثبيت ما يلي على نظامك:

*   **PHP >= 8.1**
*   **Composer**
*   **Node.js >= 16.x**
*   **npm** أو **Yarn**
*   **Git**

## خطوات الإعداد والتشغيل

اتبع هذه الخطوات لتشغيل المشروع على جهازك المحلي:

### 1. استنساخ المستودع

```bash
git clone <رابط المستودع الخاص بك>
cd website-analyzer-laravel
```

### 2. تثبيت تبعيات الواجهة الخلفية (Laravel)

```bash
composer install
```

### 3. إعداد ملف البيئة

قم بنسخ ملف `.env.example` إلى `.env`:

```bash
cp .env.example .env
```

ثم قم بإنشاء مفتاح التطبيق:

```bash
php artisan key:generate
```

### 4. إعداد قاعدة البيانات

بشكل افتراضي، يستخدم المشروع قاعدة بيانات SQLite. تأكد من وجود ملف `database.sqlite` في مجلد `database/`.

```bash
touch database/database.sqlite
```

إذا كنت ترغب في استخدام MySQL أو PostgreSQL، قم بتعديل ملف `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

ثم قم بتشغيل الهجرات لإنشاء الجداول:

```bash
php artisan migrate
```

### 5. تثبيت تبعيات الواجهة الأمامية (React)

```bash
npm install
# أو
yarn install
```

### 6. بناء أصول الواجهة الأمامية

```bash
npm run build
# أو
yarn build
```

### 7. تشغيل خادم التطوير

للوصول إلى الواجهة الخلفية والواجهة الأمامية معًا، ستحتاج إلى تشغيل خادم Laravel وخادم Vite.

**في الطرفية الأولى (للواجهة الخلفية):**

```bash
php artisan serve
```

**في الطرفية الثانية (للواجهة الأمامية):**

```bash
npm run dev
# أو
yarn dev
```

الآن، يجب أن يكون التطبيق متاحًا على `http://127.0.0.1:8000` (أو المنفذ الذي يحدده `php artisan serve`).

### 8. إنشاء مستخدم (اختياري)

يمكنك التسجيل عبر واجهة المستخدم، أو إنشاء مستخدم تجريبي باستخدام `php artisan tinker`:

```bash
php artisan tinker
>>> App\Models\User::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => bcrypt('password'),
]);
>>> exit;
```

## الاستخدام

1.  قم بزيارة `http://127.0.0.1:8000` في متصفحك.
2.  سجل الدخول باستخدام بيانات الاعتماد الخاصة بك.
3.  انتقل إلى صفحة `/analyzer` (أو استخدم رابط التنقل إذا كان متاحًا).
4.  أدخل رابط الموقع الذي ترغب في تحليله، واختر المنطقة ونوع التحليل.
5.  اضغط على زر "تحليل" للحصول على التقرير.
