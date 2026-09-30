<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostAnalytic extends Model
{
    use HasFactory;

    protected $table = 'post_analytics';

    protected $fillable = [
        'social_post_id',
        'metric_date',
        'impressions',
        'reach',
        'views',
        'likes',
        'comments',
        'shares',
        'saves',
        'clicks',
        'engagement',
        'watch_time',
        'followers_gained',
        'raw_metrics',
    ];

    protected function casts(): array
    {
        return [
            'metric_date' => 'date',
            'impressions' => 'integer',
            'reach' => 'integer',
            'views' => 'integer',
            'likes' => 'integer',
            'comments' => 'integer',
            'shares' => 'integer',
            'saves' => 'integer',
            'clicks' => 'integer',
            'engagement' => 'integer',
            'watch_time' => 'integer',
            'followers_gained' => 'integer',
            'raw_metrics' => 'array',
        ];
    }

    public function socialPost(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class);
    }
}
