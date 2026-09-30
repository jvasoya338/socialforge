<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AIProvider extends Model
{
    use HasFactory;

    protected $table = 'ai_providers';

    public const DRIVER_OPENAI = 'openai';

    public const DRIVER_OLLAMA = 'ollama';

    public const DRIVER_MOCK = 'mock';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_UNAVAILABLE = 'unavailable';

    protected $fillable = [
        'name',
        'slug',
        'driver',
        'model',
        'base_url',
        'configuration',
        'encrypted_api_key',
        'status',
        'priority',
        'is_default',
        'last_tested_at',
        'last_health_status',
    ];

    protected $hidden = [
        'encrypted_api_key',
    ];

    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'encrypted_api_key' => 'encrypted',
            'priority' => 'integer',
            'is_default' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }

    public function aiGenerations(): HasMany
    {
        return $this->hasMany(AIGeneration::class, 'ai_provider_id');
    }

    public function hasApiKey(): bool
    {
        return ! empty($this->encrypted_api_key);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
