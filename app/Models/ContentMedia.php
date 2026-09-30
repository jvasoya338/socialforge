<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ContentMedia extends Model
{
    use HasFactory;

    protected $table = 'content_media';

    public const TYPE_IMAGE = 'image';

    public const TYPE_VIDEO = 'video';

    public const TYPE_THUMBNAIL = 'thumbnail';

    public const TYPE_GIF = 'gif';

    protected $fillable = [
        'brand_id',
        'content_id',
        'type',
        'disk',
        'path',
        'url',
        'original_name',
        'thumbnail',
        'mime_type',
        'width',
        'height',
        'duration',
        'size',
        'metadata',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'duration' => 'integer',
            'size' => 'integer',
            'sort_order' => 'integer',
            'metadata' => 'array',
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

    public function publicUrl(): string
    {
        if (! empty($this->url)) {
            return $this->url;
        }

        if (str_starts_with($this->path, 'http://') || str_starts_with($this->path, 'https://')) {
            return $this->path;
        }

        return Storage::disk($this->disk ?: 'public')->url($this->path);
    }
}
