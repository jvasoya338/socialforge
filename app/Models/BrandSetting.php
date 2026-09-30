<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'default_timezone',
        'default_language',
        'posting_preferences',
        'ai_preferences',
        'approval_required',
        'auto_publish_approved',
        'require_strict_factual_check',
        'default_hashtags',
        'url_tracking_settings',
    ];

    protected function casts(): array
    {
        return [
            'posting_preferences' => 'array',
            'ai_preferences' => 'array',
            'approval_required' => 'boolean',
            'auto_publish_approved' => 'boolean',
            'require_strict_factual_check' => 'boolean',
            'default_hashtags' => 'array',
            'url_tracking_settings' => 'array',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}
