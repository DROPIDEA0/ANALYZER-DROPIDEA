<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GmbEntity extends Model
{
    use HasFactory;

    protected $fillable = [
        'place_id', 'name', 'address', 'phone', 'website',
        'latitude', 'longitude', 'address_components',
        'rating', 'total_reviews', 'rating_distribution', 'business_hours', 'photos', 'price_level',
        'categories', 'types',
        'recent_reviews', 'sentiment_score', 'sentiment_breakdown',
        'is_verified', 'status', 'attributes',
        'last_updated_from_google', 'update_frequency_days'
    ];

    protected $casts = [
        'address_components' => 'array',
        'rating_distribution' => 'array',
        'business_hours' => 'array',
        'photos' => 'array',
        'categories' => 'array',
        'types' => 'array',
        'recent_reviews' => 'array',
        'sentiment_breakdown' => 'array',
        'attributes' => 'array',
        'last_updated_from_google' => 'datetime',
        'is_verified' => 'boolean'
    ];

    // Relations
    public function competitors()
    {
        return $this->hasMany(Competitor::class, 'main_place_id', 'place_id');
    }

    public function competingWith()
    {
        return $this->hasMany(Competitor::class, 'competitor_place_id', 'place_id');
    }

    // Scopes
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeNearby($query, $lat, $lng, $radius = 10)
    {
        return $query->selectRaw(
            "*, 
             ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance",
            [$lat, $lng, $lat]
        )->having('distance', '<', $radius)
         ->orderBy('distance');
    }

    // Accessors
    public function getFullAddressAttribute()
    {
        return $this->address;
    }

    public function getAverageRatingAttribute()
    {
        return round($this->rating, 1);
    }
}
