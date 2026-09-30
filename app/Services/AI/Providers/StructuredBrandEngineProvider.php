<?php

namespace App\Services\AI\Providers;

use App\Models\AIProvider;
use App\Services\AI\Contracts\AIProviderInterface;
use Illuminate\Support\Str;

class StructuredBrandEngineProvider implements AIProviderInterface
{
    public function __construct(protected AIProvider $providerModel) {}

    public function generate(string $systemPrompt, string $userPrompt, array $options = []): array
    {
        $brandName = (string) ($options['brand_name'] ?? 'Brand');
        $tone = strtolower((string) ($options['brand_tone'] ?? 'professional'));
        $platformSlug = strtolower((string) ($options['platform_slug'] ?? 'facebook'));
        $title = trim((string) ($options['content_title'] ?? 'Featured Update'));
        $summary = trim((string) ($options['content_summary'] ?? $options['content_body'] ?? ''));
        $strictFacts = (bool) ($options['strict_facts'] ?? false);
        $preferredPhrases = (array) ($options['preferred_phrases'] ?? []);

        $leadPhrase = $preferredPhrases[0] ?? null;

        if ($strictFacts || str_contains($tone, 'factual') || str_contains($tone, 'neutral')) {
            // News / factual composition: preserve exact facts and avoid sensationalism
            $coreFact = $summary !== '' ? Str::limit($summary, 180, '...') : $title;
            $body = "{$title} — {$coreFact}";
            if ($leadPhrase && ! str_contains($body, $leadPhrase)) {
                $body .= ' '.$leadPhrase;
            }
        } elseif (str_contains($tone, 'gaming') || str_contains($tone, 'energetic')) {
            // Energetic / Gaming composition
            $hook = $platformSlug === 'x'
                ? "{$title} is live on {$brandName}!"
                : "Ready for your next browser challenge? {$title} just dropped on {$brandName}!";
            $detail = $summary !== '' ? Str::limit($summary, 150, '...') : 'Jump straight into the action with zero downloads.';
            $cta = $leadPhrase ?: 'Play instantly in your browser and set the top score!';
            $body = trim("{$hook} {$detail} {$cta}");
        } elseif (str_contains($tone, 'elegant') || str_contains($tone, 'premium')) {
            // Luxury / Jewelry / E-commerce composition
            $detail = $summary !== '' ? Str::limit($summary, 170, '...') : 'Thoughtfully crafted with enduring elegance.';
            $cta = $leadPhrase ?: 'Explore the collection.';
            $body = trim("{$title}. {$detail} {$cta}");
        } else {
            // General adaptable brand composition
            $detail = $summary !== '' ? Str::limit($summary, 170, '...') : '';
            $body = trim("{$title}. {$detail} ".($leadPhrase ?? ''));
        }

        // Platform length adaptation
        $maxLength = (int) ($options['max_length'] ?? 2000);
        if ($platformSlug === 'x' || $maxLength <= 280) {
            $body = Str::limit($body, 210, '...');
        } elseif ($platformSlug === 'pinterest') {
            $body = Str::limit($body, 400, '...');
        }

        $inputTokens = max(20, (int) (strlen($systemPrompt.$userPrompt) / 4));
        $outputTokens = max(15, (int) (strlen($body) / 4));

        return [
            'text' => $body,
            'model' => $this->providerModel->model ?: 'sf-brand-composer-v1',
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'estimated_cost' => 0.0,
        ];
    }

    public function testConnection(): bool
    {
        return true;
    }

    public function models(): array
    {
        return ['sf-brand-composer-v1'];
    }

    public function healthCheck(): array
    {
        return [
            'healthy' => true,
            'message' => 'SocialForge Structured Brand Engine is operational.',
        ];
    }
}
