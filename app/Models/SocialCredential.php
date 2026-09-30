<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'social_account_id',
        'access_token',
        'refresh_token',
        'token_type',
        'expires_at',
        'refresh_expires_at',
        'scopes',
        'metadata',
        'last_refreshed_at',
        'last_verified_at',
        'last_refresh_error',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'metadata' => 'encrypted:array',
            'scopes' => 'array',
            'expires_at' => 'datetime',
            'refresh_expires_at' => 'datetime',
            'last_refreshed_at' => 'datetime',
            'last_verified_at' => 'datetime',
        ];
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isExpiringSoon(int $hours = 48): bool
    {
        if (! $this->expires_at || $this->isExpired()) {
            return false;
        }

        return $this->expires_at->lte(now()->addHours($hours));
    }

    public function hasValidAccessToken(): bool
    {
        return ! empty($this->access_token) && ! $this->isExpired();
    }

    public function canRefresh(): bool
    {
        if (empty($this->refresh_token) && empty($this->access_token)) {
            return false;
        }

        if ($this->refresh_expires_at && $this->refresh_expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
