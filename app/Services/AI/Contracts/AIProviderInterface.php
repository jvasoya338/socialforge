<?php

namespace App\Services\AI\Contracts;

interface AIProviderInterface
{
    /**
     * Generate text from system and user prompts.
     *
     * @param  array<string, mixed>  $options
     * @return array{text: string, model: string, input_tokens: int, output_tokens: int, estimated_cost: float}
     */
    public function generate(string $systemPrompt, string $userPrompt, array $options = []): array;

    public function testConnection(): bool;

    /**
     * @return array<int, string>
     */
    public function models(): array;

    /**
     * @return array{healthy: bool, message: string}
     */
    public function healthCheck(): array;
}
