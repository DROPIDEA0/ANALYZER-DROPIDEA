<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiApiSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'api_key',
        'api_base_url',
        'model',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    protected $hidden = [
        'api_key',
    ];

    /**
     * العلاقة مع المستخدم
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * الحصول على API key مشفر للعرض
     */
    public function getMaskedApiKeyAttribute(): string
    {
        if (!$this->api_key) {
            return '';
        }
        
        $key = $this->api_key;
        if (strlen($key) <= 8) {
            return str_repeat('*', strlen($key));
        }
        
        return substr($key, 0, 4) . str_repeat('*', strlen($key) - 8) . substr($key, -4);
    }

    /**
     * التحقق من صحة الإعدادات
     */
    public function isValid(): bool
    {
        return !empty($this->api_key) && $this->is_active;
    }

    /**
     * الحصول على الإعدادات الافتراضية لكل مزود
     */
    public static function getDefaultSettings(string $provider): array
    {
        return match ($provider) {
            'openai' => [
                'model' => 'gpt-4o-mini',
                'max_tokens' => 1200,
                'temperature' => 0.7,
                'api_base_url' => 'https://api.openai.com/v1',
            ],
            'anthropic' => [
                'model' => 'claude-3-opus-20240229',
                'max_tokens' => 4000,
                'temperature' => 0.7,
                'api_base_url' => 'https://api.anthropic.com',
            ],
            'manus' => [
                'model' => 'manus-ai',
                'max_tokens' => 4000,
                'temperature' => 0.7,
                'api_base_url' => 'https://api.manus.im',
            ],
            default => [],
        };
    }

    /**
     * الحصول على قائمة المزودين المتاحين
     */
    public static function getAvailableProviders(): array
    {
        return [
            'openai' => [
                'name' => 'OpenAI GPT',
                'description' => 'تحليل المحتوى والسيو باستخدام GPT-4',
                'icon' => '🧠',
                'models' => ['gpt-4o-mini', 'gpt-4o', 'gpt-4', 'gpt-3.5-turbo', 'gpt-4-turbo'],
                'api_base_url' => 'https://api.openai.com/v1',
            ],
            'anthropic' => [
                'name' => 'Anthropic Claude',
                'description' => 'تحليل تجربة المستخدم والمحتوى',
                'icon' => '🤖',
                'models' => ['claude-3-opus-20240229', 'claude-3-sonnet-20240229', 'claude-3-haiku-20240307'],
                'api_base_url' => 'https://api.anthropic.com',
            ],
            'manus' => [
                'name' => 'Manus AI',
                'description' => 'تحليل شامل للأداء والتقنيات',
                'icon' => '🔮',
                'models' => ['manus-ai', 'manus-pro'],
                'api_base_url' => 'https://api.manus.im',
            ],
        ];
    }

    /**
     * الحصول على معلومات المزود
     */
    public function getProviderInfo(): array
    {
        $providers = self::getAvailableProviders();
        return $providers[$this->provider] ?? [];
    }

    /**
     * تشفير API key قبل الحفظ
     */
    public function setApiKeyAttribute($value): void
    {
        if ($value) {
            $this->attributes['api_key'] = encrypt($value);
        }
    }

    /**
     * فك تشفير API key عند القراءة
     */
    public function getApiKeyAttribute($value): ?string
    {
        if ($value) {
            try {
                return decrypt($value);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }
}
