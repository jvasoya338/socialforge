<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'name',
        'slug',
        'website_url',
        'logo',
        'description',
        'timezone',
        'default_language',
        'status',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function brandSetting(): HasOne
    {
        return $this->hasOne(BrandSetting::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function contentSources(): HasMany
    {
        return $this->hasMany(ContentSource::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }

    public function contentMedia(): HasMany
    {
        return $this->hasMany(ContentMedia::class);
    }

    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    public function brandVoiceSetting(): HasOne
    {
        return $this->hasOne(BrandVoiceSetting::class);
    }

    public function brandVoiceExamples(): HasMany
    {
        return $this->hasMany(BrandVoiceExample::class);
    }

    public function aiGenerations(): HasMany
    {
        return $this->hasMany(AIGeneration::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function requiresApproval(): bool
    {
        if ($this->brandSetting) {
            return (bool) $this->brandSetting->approval_required;
        }

        return (bool) ($this->settings['approval_required'] ?? true);
    }

    public function autoPublishesApproved(): bool
    {
        if ($this->brandSetting) {
            return (bool) $this->brandSetting->auto_publish_approved;
        }

        return (bool) ($this->settings['auto_publish_approved'] ?? false);
    }

    public function requiresStrictFactualCheck(): bool
    {
        if ($this->brandSetting && $this->brandSetting->require_strict_factual_check) {
            return true;
        }

        if ($this->brandVoiceSetting && $this->brandVoiceSetting->preserve_strict_facts) {
            return true;
        }

        return (bool) ($this->settings['require_strict_factual_check'] ?? false);
    }
}
