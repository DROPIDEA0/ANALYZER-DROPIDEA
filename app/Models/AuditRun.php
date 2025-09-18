<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'website_analysis_id', 'audit_type', 'status', 'job_id',
        'started_at', 'completed_at', 'execution_time_seconds', 'attempts', 'max_attempts',
        'result_data', 'error_details', 'error_message', 'debug_info',
        'memory_usage_mb', 'cpu_usage_seconds',
        'api_calls_made', 'api_response_times'
    ];

    protected $casts = [
        'result_data' => 'array',
        'error_details' => 'array',
        'debug_info' => 'array',
        'api_response_times' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    // Relations
    public function websiteAnalysis()
    {
        return $this->belongsTo(WebsiteAnalysisAdvanced::class, 'website_analysis_id');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('audit_type', $type);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('started_at', '>=', now()->subHours($hours));
    }

    // Accessors
    public function getIsSuccessfulAttribute()
    {
        return $this->status === 'completed';
    }

    public function getDurationAttribute()
    {
        if ($this->started_at && $this->completed_at) {
            return $this->started_at->diffInSeconds($this->completed_at);
        }
        return $this->execution_time_seconds;
    }

    public function getFormattedDurationAttribute()
    {
        $duration = $this->duration;
        if (!$duration) return 'N/A';
        
        if ($duration < 60) {
            return $duration . 's';
        }
        
        return gmdate('i:s', $duration);
    }
}
