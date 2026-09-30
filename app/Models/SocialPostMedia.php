<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialPostMedia extends Model
{
    use HasFactory;

    protected $table = 'social_post_media';

    protected $fillable = [
        'social_post_id',
        'content_media_id',
        'type',
        'path',
        'thumbnail',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function socialPost(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class);
    }

    public function contentMedia(): BelongsTo
    {
        return $this->belongsTo(ContentMedia::class);
    }
}
