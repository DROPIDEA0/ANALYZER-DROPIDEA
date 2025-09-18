<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'url',
        'region',
        'analysis_type',
        'analysis_data',
        'seo_score',
        'performance_score',
        'load_time',
        'ai_score',
    ];

    protected $casts = [
        'analysis_data' => 'array',
        'load_time' => 'decimal:2',
    ];

    /**
     * العلاقة مع المستخدم
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope للتحليلات الحديثة
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope للتحليلات حسب النوع
     */
    public function scopeByType($query, $type)
    {
        return $query->where('analysis_type', $type);
    }
}
