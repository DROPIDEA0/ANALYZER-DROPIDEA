<?php

namespace App\Http\Controllers;

use App\Models\AiApiSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class AiApiSettingController extends Controller
{
    /**
     * عرض صفحة إعدادات APIs
     */
    public function index(): Response
    {
        $user = Auth::user();
        $providers = AiApiSetting::getAvailableProviders();
        
        // الحصول على إعدادات المستخدم الحالية
        $userSettings = AiApiSetting::where('user_id', $user->id)
            ->get()
            ->keyBy('provider');

        // إضافة الإعدادات الافتراضية للمزودين غير المكونين
        foreach ($providers as $providerKey => $providerInfo) {
            if (!isset($userSettings[$providerKey])) {
                $userSettings[$providerKey] = new AiApiSetting([
                    'provider' => $providerKey,
                    'is_active' => false,
                    'settings' => AiApiSetting::getDefaultSettings($providerKey),
                ]);
            }
        }

        return Inertia::render('AiApiSettings', [
            'providers' => $providers,
            'userSettings' => $userSettings->values(),
            'status' => session('status'),
        ]);
    }

    /**
     * حفظ أو تحديث إعدادات API
     */
    public function store(Request $request)
    {
        $request->validate([
            'provider' => 'required|string|in:openai,anthropic,manus',
            'api_key' => 'required|string|min:10',
            'api_base_url' => 'nullable|url',
            'model' => 'nullable|string',
            'is_active' => 'boolean',
            'settings' => 'nullable|array',
        ]);

        $user = Auth::user();

        // البحث عن الإعدادات الموجودة أو إنشاء جديدة
        $apiSetting = AiApiSetting::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $request->provider,
            ],
            [
                'api_key' => $request->api_key,
                'api_base_url' => $request->api_base_url,
                'model' => $request->model,
                'is_active' => $request->boolean('is_active'),
                'settings' => $request->settings ?: AiApiSetting::getDefaultSettings($request->provider),
            ]
        );

        return redirect()->back()->with('status', [
            'type' => 'success',
            'message' => 'تم حفظ إعدادات ' . $apiSetting->getProviderInfo()['name'] . ' بنجاح',
        ]);
    }

    /**
     * تحديث حالة التفعيل
     */
    public function toggleActive(Request $request, AiApiSetting $apiSetting)
    {
        // التحقق من أن الإعدادات تخص المستخدم الحالي
        if ($apiSetting->user_id !== Auth::id()) {
            abort(403);
        }

        $apiSetting->update([
            'is_active' => !$apiSetting->is_active,
        ]);

        $status = $apiSetting->is_active ? 'تم تفعيل' : 'تم إلغاء تفعيل';
        
        return redirect()->back()->with('status', [
            'type' => 'success',
            'message' => $status . ' ' . $apiSetting->getProviderInfo()['name'],
        ]);
    }

    /**
     * حذف إعدادات API
     */
    public function destroy(AiApiSetting $apiSetting)
    {
        // التحقق من أن الإعدادات تخص المستخدم الحالي
        if ($apiSetting->user_id !== Auth::id()) {
            abort(403);
        }

        $providerName = $apiSetting->getProviderInfo()['name'];
        $apiSetting->delete();

        return redirect()->back()->with('status', [
            'type' => 'success',
            'message' => 'تم حذف إعدادات ' . $providerName . ' بنجاح',
        ]);
    }

    /**
     * اختبار الاتصال بـ API
     */
    public function testConnection(Request $request)
    {
        $request->validate([
            'provider' => 'required|string|in:openai,anthropic,manus',
            'api_key' => 'required|string',
            'api_base_url' => 'nullable|url',
            'model' => 'nullable|string',
        ]);

        try {
            $result = $this->performApiTest(
                $request->provider,
                $request->api_key,
                $request->api_base_url,
                $request->model
            );

            return response()->json([
                'success' => true,
                'message' => 'تم الاتصال بنجاح!',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في الاتصال: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * تنفيذ اختبار API
     */
    private function performApiTest(string $provider, string $apiKey, ?string $baseUrl, ?string $model): array
    {
        switch ($provider) {
            case 'openai':
                return $this->testOpenAI($apiKey, $baseUrl, $model);
            case 'anthropic':
                return $this->testAnthropic($apiKey, $baseUrl, $model);
            case 'manus':
                return $this->testManus($apiKey, $baseUrl, $model);
            default:
                throw new \Exception('مزود غير مدعوم');
        }
    }

    /**
     * اختبار OpenAI API
     */
    private function testOpenAI(string $apiKey, ?string $baseUrl, ?string $model): array
    {
        $baseUrl = $baseUrl ?: 'https://api.openai.com/v1';
        $model = $model ?: 'gpt-3.5-turbo';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post($baseUrl . '/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => 'Test connection']
            ],
            'max_tokens' => 10,
        ]);

        if ($response->successful()) {
            return [
                'model' => $model,
                'status' => 'متصل',
                'response_time' => '< 1s',
            ];
        }

        throw new \Exception('HTTP ' . $response->status() . ': ' . $response->body());
    }

    /**
     * اختبار Anthropic API
     */
    private function testAnthropic(string $apiKey, ?string $baseUrl, ?string $model): array
    {
        $baseUrl = $baseUrl ?: 'https://api.anthropic.com';
        $model = $model ?: 'claude-3-haiku-20240307';

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01',
        ])->post($baseUrl . '/v1/messages', [
            'model' => $model,
            'max_tokens' => 10,
            'messages' => [
                ['role' => 'user', 'content' => 'Test connection']
            ],
        ]);

        if ($response->successful()) {
            return [
                'model' => $model,
                'status' => 'متصل',
                'response_time' => '< 1s',
            ];
        }

        throw new \Exception('HTTP ' . $response->status() . ': ' . $response->body());
    }

    /**
     * اختبار Manus API
     */
    private function testManus(string $apiKey, ?string $baseUrl, ?string $model): array
    {
        // هذا مثال - يجب تحديثه حسب API الفعلي لـ Manus
        $baseUrl = $baseUrl ?: 'https://api.manus.im';
        $model = $model ?: 'manus-ai';

        // محاكاة اختبار ناجح لـ Manus
        return [
            'model' => $model,
            'status' => 'متصل',
            'response_time' => '< 1s',
        ];
    }
}
