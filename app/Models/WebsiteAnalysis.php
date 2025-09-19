<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteAnalysis extends Model
{
    use HasFactory;
    
    protected $table = 'website_analyses';

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
        'country',
        'gmb_place_id',
        'gmb_name',
        'gmb_address',
        'gmb_latitude',
        'gmb_longitude',
        'gmb_rating',
        'gmb_reviews_count',
        'gmb_data',
        'competitors_data',
        'composite_score'
    ];

    protected $casts = [
        'analysis_data' => 'array',
        'gmb_data' => 'array',
        'competitors_data' => 'array',
        'load_time' => 'decimal:2',
        'gmb_latitude' => 'decimal:8',
        'gmb_longitude' => 'decimal:8',
        'gmb_rating' => 'decimal:2'
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
