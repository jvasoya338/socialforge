<?php

namespace App\Services\AI\Providers;

use App\Exceptions\AIProviderException;
use App\Models\AIProvider;
use App\Services\AI\Contracts\AIProviderInterface;
use Illuminate\Support\Facades\Http;

class OpenAIProvider implements AIProviderInterface
{
    public function __construct(protected AIProvider $providerModel) {}

    protected function apiKey(): string
    {
        return (string) ($this->providerModel->encrypted_api_key ?: config('services.openai.api_key', ''));
    }

    protected function baseUrl(): string
    {
        return rtrim((string) ($this->providerModel->base_url ?: config('services.openai.base_url', 'https://api.openai.com/v1')), '/');
    }

    public function generate(string $systemPrompt, string $userPrompt, array $options = []): array
    {
        $apiKey = $this->apiKey();
        if (empty($apiKey)) {
            throw new AIProviderException('OpenAI API key is not configured.', 'openai');
        }

        $model = (string) ($options['model'] ?? $this->providerModel->model ?: 'gpt-4o-mini');

        $response = Http::timeout(30)
            ->withToken($apiKey)
            ->post($this->baseUrl().'/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => (float) ($options['temperature'] ?? $this->providerModel->configuration['temperature'] ?? 0.7),
            ]);

        if (! $response->successful()) {
            throw new AIProviderException(
                'OpenAI generation failed: '.($response->json('error.message') ?? $response->status()),
                'openai',
                $response->status()
            );
        }

        $data = $response->json();
        $text = trim((string) ($data['choices'][0]['message']['content'] ?? ''));
        $inputTokens = (int) ($data['usage']['prompt_tokens'] ?? 0);
        $outputTokens = (int) ($data['usage']['completion_tokens'] ?? 0);
        $cost = round((($inputTokens * 0.00015) + ($outputTokens * 0.0006)) / 1000, 6);

        return [
            'text' => $text,
            'model' => $model,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'estimated_cost' => $cost,
        ];
    }

    public function testConnection(): bool
    {
        return $this->healthCheck()['healthy'];
    }

    public function models(): array
    {
        $apiKey = $this->apiKey();
        if (empty($apiKey)) {
            return ['gpt-4o-mini', 'gpt-4o'];
        }

        try {
            $response = Http::timeout(10)->withToken($apiKey)->get($this->baseUrl().'/models');
            if ($response->successful()) {
                return collect($response->json('data', []))->pluck('id')->take(20)->values()->all();
            }
        } catch (\Throwable) {
        }

        return ['gpt-4o-mini', 'gpt-4o'];
    }

    public function healthCheck(): array
    {
        $apiKey = $this->apiKey();
        if (empty($apiKey)) {
            return [
                'healthy' => false,
                'message' => 'API key not configured in .env or AI Provider settings.',
            ];
        }

        try {
            $response = Http::timeout(10)->withToken($apiKey)->get($this->baseUrl().'/models');
            if ($response->successful()) {
                return ['healthy' => true, 'message' => 'Connected to OpenAI API.'];
            }

            return ['healthy' => false, 'message' => 'OpenAI returned HTTP '.$response->status()];
        } catch (\Throwable $e) {
            return ['healthy' => false, 'message' => 'OpenAI connection error: '.$e->getMessage()];
        }
    }
}
