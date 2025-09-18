<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteAnalysisAdvanced extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'url', 'domain', 'status',
        'core_web_vitals', 'pagespeed_mobile', 'pagespeed_desktop',
        'lighthouse_performance', 'lighthouse_seo', 'lighthouse_accessibility', 'lighthouse_best_practices',
        'page_size_mb', 'total_requests', 'compression_details', 'http_version',
        'stack_detection', 'technologies',
        'security_headers', 'has_ssl', 'ssl_grade', 'security_issues',
        'accessibility_results', 'accessibility_score', 'accessibility_violations',
        'metadata', 'open_graph', 'twitter_cards', 'schema_org',
        'has_robots_txt', 'has_sitemap', 'canonical_issues', 'indexing_status',
        'composite_score', 'seo_score', 'performance_score', 'security_score', 'ux_score', 'maps_presence_score',
        'analysis_started_at', 'analysis_completed_at', 'total_analysis_time'
    ];

    protected $casts = [
        'core_web_vitals' => 'array',
        'compression_details' => 'array',
        'stack_detection' => 'array',
        'technologies' => 'array',
        'security_headers' => 'array',
        'security_issues' => 'array',
        'accessibility_results' => 'array',
        'accessibility_violations' => 'array',
        'metadata' => 'array',
        'open_graph' => 'array',
        'twitter_cards' => 'array',
        'schema_org' => 'array',
        'canonical_issues' => 'array',
        'indexing_status' => 'array',
        'analysis_started_at' => 'datetime',
        'analysis_completed_at' => 'datetime',
        'has_ssl' => 'boolean',
        'has_robots_txt' => 'boolean',
        'has_sitemap' => 'boolean'
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auditRuns()
    {
        return $this->hasMany(AuditRun::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByDomain($query, $domain)
    {
        return $query->where('domain', $domain);
    }

    // Accessors
    public function getIsCompletedAttribute()
    {
        return $this->status === 'completed';
    }

    public function getAnalysisDurationAttribute()
    {
        if ($this->analysis_started_at && $this->analysis_completed_at) {
            return $this->analysis_started_at->diffInSeconds($this->analysis_completed_at);
        }
        return null;
    }
}
