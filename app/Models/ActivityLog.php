<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    use HasFactory;

    public const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'raw_api_key',
        'encrypted_api_key',
        'encrypted_credentials',
        'secret',
        'client_secret',
        'app_secret',
        'webhook_secret',
        'authorization',
    ];

    protected $fillable = [
        'brand_id',
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public static function sanitizeProperties(array $properties): array
    {
        $sanitized = [];

        foreach ($properties as $key => $value) {
            $normalizedKey = strtolower((string) $key);
            $isSensitive = false;

            foreach (self::SENSITIVE_KEYS as $sensitive) {
                if ($normalizedKey === $sensitive || str_contains($normalizedKey, $sensitive)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitizeProperties($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    public static function record(
        string $action,
        string $description,
        ?Model $subject = null,
        ?int $brandId = null,
        array $properties = [],
        ?int $userId = null
    ): self {
        if ($brandId === null && $subject !== null) {
            if ($subject instanceof Brand) {
                $brandId = $subject->id;
            } elseif (isset($subject->brand_id)) {
                $brandId = $subject->brand_id;
            }
        }

        $request = app()->bound('request') ? request() : null;

        return self::create([
            'brand_id' => $brandId,
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => ! empty($properties) ? self::sanitizeProperties($properties) : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 250) : null,
        ]);
    }
}
