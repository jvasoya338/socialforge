<?php

namespace App\Services\AI;

use App\Exceptions\AIProviderException;
use App\Models\AIProvider;
use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\Providers\OllamaProvider;
use App\Services\AI\Providers\OpenAIProvider;
use App\Services\AI\Providers\StructuredBrandEngineProvider;

class AIProviderManager
{
    public function resolve(?AIProvider $provider = null): AIProviderInterface
    {
        if ($provider === null) {
            $provider = $this->resolveDefaultModel();
        }

        return match ($provider->driver) {
            AIProvider::DRIVER_OPENAI => new OpenAIProvider($provider),
            AIProvider::DRIVER_OLLAMA => new OllamaProvider($provider),
            default => new StructuredBrandEngineProvider($provider),
        };
    }

    public function resolveDefaultModel(): AIProvider
    {
        $default = AIProvider::query()
            ->where('status', AIProvider::STATUS_ACTIVE)
            ->orderByDesc('is_default')
            ->orderBy('priority')
            ->first();

        if (! $default) {
            throw new AIProviderException('No active AI provider is configured in SocialForge.');
        }

        // If default is OpenAI but no API key is configured, fall back to active structured engine
        if ($default->driver === AIProvider::DRIVER_OPENAI && ! $default->hasApiKey() && empty(config('services.openai.api_key'))) {
            $fallback = AIProvider::query()
                ->where('status', AIProvider::STATUS_ACTIVE)
                ->where('driver', AIProvider::DRIVER_MOCK)
                ->first();

            if ($fallback) {
                return $fallback;
            }
        }

        return $default;
    }
}
