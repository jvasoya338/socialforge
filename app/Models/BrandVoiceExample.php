<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandVoiceExample extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'social_platform_id',
        'content',
        'source',
        'approved',
        'performance_score',
    ];

    protected function casts(): array
    {
        return [
            'approved' => 'boolean',
            'performance_score' => 'decimal:2',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function socialPlatform(): BelongsTo
    {
        return $this->belongsTo(SocialPlatform::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('approved', true);
    }
}
