<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountAnalytic extends Model
{
    use HasFactory;

    protected $table = 'account_analytics';

    protected $fillable = [
        'social_account_id',
        'metric_date',
        'followers_count',
        'following_count',
        'impressions',
        'reach',
        'profile_views',
        'engagement_rate',
        'raw_metrics',
    ];

    protected function casts(): array
    {
        return [
            'metric_date' => 'date',
            'followers_count' => 'integer',
            'following_count' => 'integer',
            'impressions' => 'integer',
            'reach' => 'integer',
            'profile_views' => 'integer',
            'engagement_rate' => 'decimal:2',
            'raw_metrics' => 'array',
        ];
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }
}
