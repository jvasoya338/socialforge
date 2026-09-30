<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AIGeneration extends Model
{
    use HasFactory;

    protected $table = 'ai_generations';

    public const TYPE_CONTENT_GENERATION = 'content_generation';

    public const TYPE_HUMANIZATION = 'humanization';

    public const TYPE_PLATFORM_ADAPTATION = 'platform_adaptation';

    public const TYPE_REGENERATION = 'regeneration';

    public const TYPE_FACT_CHECK = 'fact_check';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_PROCESSING = 'processing';

    protected $fillable = [
        'brand_id',
        'content_id',
        'social_platform_id',
        'ai_provider_id',
        'prompt_template_id',
        'prompt_version',
        'generation_type',
        'input',
        'output',
        'model',
        'input_tokens',
        'output_tokens',
        'estimated_cost',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'prompt_version' => 'integer',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'estimated_cost' => 'decimal:6',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function socialPlatform(): BelongsTo
    {
        return $this->belongsTo(SocialPlatform::class);
    }

    public function aiProvider(): BelongsTo
    {
        return $this->belongsTo(AIProvider::class, 'ai_provider_id');
    }

    public function promptTemplate(): BelongsTo
    {
        return $this->belongsTo(PromptTemplate::class);
    }

    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class, 'ai_generation_id');
    }
}
