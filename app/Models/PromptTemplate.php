<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromptTemplate extends Model
{
    use HasFactory;

    public const PURPOSE_CONTENT_GENERATION = 'content_generation';

    public const PURPOSE_HUMANIZATION = 'humanization';

    public const PURPOSE_HEADLINE = 'headline';

    public const PURPOSE_HASHTAGS = 'hashtags';

    public const PURPOSE_CTA = 'cta';

    public const PURPOSE_SUMMARY = 'summary';

    public const PURPOSE_PLATFORM_ADAPTATION = 'platform_adaptation';

    public const PURPOSE_FACT_CHECK = 'fact_check';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'name',
        'slug',
        'purpose',
        'system_prompt',
        'user_prompt_template',
        'version',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
        ];
    }

    public function aiGenerations(): HasMany
    {
        return $this->hasMany(AIGeneration::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
