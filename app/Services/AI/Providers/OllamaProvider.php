<?php

namespace App\Services\AI\Providers;

use App\Exceptions\AIProviderException;
use App\Models\AIProvider;
use App\Services\AI\Contracts\AIProviderInterface;
use Illuminate\Support\Facades\Http;

class OllamaProvider implements AIProviderInterface
{
    public function __construct(protected AIProvider $providerModel) {}

    protected function baseUrl(): string
    {
        return rtrim((string) ($this->providerModel->base_url ?: config('services.ollama.base_url', 'http://127.0.0.1:11434')), '/');
    }

    public function generate(string $systemPrompt, string $userPrompt, array $options = []): array
    {
        $model = (string) ($options['model'] ?? $this->providerModel->model ?: 'llama3.1');

        try {
            $response = Http::timeout(45)->post($this->baseUrl().'/api/chat', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'stream' => false,
            ]);
        } catch (\Throwable $e) {
            throw new AIProviderException('Ollama server unreachable: '.$e->getMessage(), 'ollama', 0, $e);
        }

        if (! $response->successful()) {
            throw new AIProviderException('Ollama generation error: HTTP '.$response->status(), 'ollama', $response->status());
        }

        $data = $response->json();
        $text = trim((string) ($data['message']['content'] ?? ''));

        return [
            'text' => $text,
            'model' => $model,
            'input_tokens' => (int) ($data['prompt_eval_count'] ?? 0),
            'output_tokens' => (int) ($data['eval_count'] ?? 0),
            'estimated_cost' => 0.0,
        ];
    }

    public function testConnection(): bool
    {
        return $this->healthCheck()['healthy'];
    }

    public function models(): array
    {
        try {
            $response = Http::timeout(5)->get($this->baseUrl().'/api/tags');
            if ($response->successful()) {
                return collect($response->json('models', []))->pluck('name')->values()->all();
            }
        } catch (\Throwable) {
        }

        return ['llama3.1', 'mistral'];
    }

    public function healthCheck(): array
    {
        try {
            $response = Http::timeout(5)->get($this->baseUrl().'/api/tags');
            if ($response->successful()) {
                return ['healthy' => true, 'message' => 'Ollama local server is online.'];
            }

            return ['healthy' => false, 'message' => 'Ollama returned HTTP '.$response->status()];
        } catch (\Throwable $e) {
            return ['healthy' => false, 'message' => 'Ollama server not running at '.$this->baseUrl()];
        }
    }
}
