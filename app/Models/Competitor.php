<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Competitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'main_place_id', 'main_domain',
        'competitor_place_id', 'competitor_domain', 'competitor_name', 'competitor_website',
        'latitude', 'longitude', 'distance_km',
        'competitor_rating', 'competitor_reviews', 'competitor_composite_score',
        'competitive_advantages', 'competitive_gaps', 'similarity_score',
        'discovery_method', 'is_direct_competitor'
    ];

    protected $casts = [
        'competitive_advantages' => 'array',
        'competitive_gaps' => 'array',
        'is_direct_competitor' => 'boolean'
    ];

    // Relations
    public function mainGmbEntity()
    {
        return $this->belongsTo(GmbEntity::class, 'main_place_id', 'place_id');
    }

    public function competitorGmbEntity()
    {
        return $this->belongsTo(GmbEntity::class, 'competitor_place_id', 'place_id');
    }

    // Scopes
    public function scopeDirectCompetitors($query)
    {
        return $query->where('is_direct_competitor', true);
    }

    public function scopeNearby($query, $maxDistance = 5)
    {
        return $query->where('distance_km', '<=', $maxDistance);
    }

    public function scopeByDiscoveryMethod($query, $method)
    {
        return $query->where('discovery_method', $method);
    }

    // Accessors
    public function getCompetitiveStrengthAttribute()
    {
        if (!$this->competitor_composite_score) return null;
        
        if ($this->competitor_composite_score > 80) return 'strong';
        if ($this->competitor_composite_score > 60) return 'moderate';
        return 'weak';
    }
}
