<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandVoiceSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'tone',
        'writing_style',
        'audience',
        'emoji_style',
        'cta_style',
        'hashtag_style',
        'default_language',
        'words_to_avoid',
        'phrases_to_avoid',
        'preferred_phrases',
        'additional_instructions',
        'preserve_strict_facts',
    ];

    protected function casts(): array
    {
        return [
            'words_to_avoid' => 'array',
            'phrases_to_avoid' => 'array',
            'preferred_phrases' => 'array',
            'preserve_strict_facts' => 'boolean',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}
