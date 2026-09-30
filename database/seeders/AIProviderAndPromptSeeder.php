<?php

namespace Database\Seeders;

use App\Models\AIProvider;
use App\Models\PromptTemplate;
use Illuminate\Database\Seeder;

class AIProviderAndPromptSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'name' => 'OpenAI GPT-4o Mini',
                'slug' => 'openai-gpt4o-mini',
                'driver' => AIProvider::DRIVER_OPENAI,
                'model' => config('services.openai.model', 'gpt-4o-mini'),
                'base_url' => config('services.openai.base_url', 'https://api.openai.com/v1'),
                'status' => AIProvider::STATUS_ACTIVE,
                'priority' => 1,
                'is_default' => false,
                'configuration' => [
                    'temperature' => 0.7,
                    'max_tokens' => 800,
                ],
            ],
            [
                'name' => 'Ollama Local LLM',
                'slug' => 'ollama-local',
                'driver' => AIProvider::DRIVER_OLLAMA,
                'model' => config('services.ollama.model', 'llama3.1'),
                'base_url' => config('services.ollama.base_url', 'http://127.0.0.1:11434'),
                'status' => AIProvider::STATUS_ACTIVE,
                'priority' => 2,
                'is_default' => false,
                'configuration' => [
                    'temperature' => 0.6,
                ],
            ],
            [
                'name' => 'SocialForge Structured Engine (Development/Fallback)',
                'slug' => 'socialforge-structured-engine',
                'driver' => AIProvider::DRIVER_MOCK,
                'model' => 'sf-brand-composer-v1',
                'base_url' => null,
                'status' => AIProvider::STATUS_ACTIVE,
                'priority' => 10,
                'is_default' => true,
                'configuration' => [
                    'deterministic_brand_aware' => true,
                ],
            ],
        ];

        foreach ($providers as $provider) {
            AIProvider::query()->updateOrCreate(
                ['slug' => $provider['slug']],
                $provider
            );
        }

        $prompts = [
            [
                'name' => 'Brand-Aware Social Post Generation',
                'slug' => 'default-content-generation',
                'purpose' => PromptTemplate::PURPOSE_CONTENT_GENERATION,
                'system_prompt' => 'You are the dedicated social media strategist for {{brand_name}}. Write in the brand tone ({{brand_tone}}) and style ({{brand_style}}). Target audience: {{brand_audience}}. Never use forbidden words ({{words_to_avoid}}) or forbidden phrases ({{phrases_to_avoid}}). Preserve all factual details, numbers, dates, names, and URLs.',
                'user_prompt_template' => "Source Title: {{content_title}}\nType: {{content_type}}\nSummary: {{content_summary}}\nBody: {{content_body}}\nSource URL: {{source_url}}\nTarget Platform: {{platform_name}} (Max length: {{max_length}} chars)\n\nApproved Style Examples:\n{{voice_examples}}\n\nAdditional Brand Instructions:\n{{additional_instructions}}\n\nWrite an engaging, platform-native social post for {{platform_name}}.",
                'version' => 1,
                'status' => PromptTemplate::STATUS_ACTIVE,
            ],
            [
                'name' => 'Editorial Quality & Brand Voice Humanization',
                'slug' => 'default-humanization',
                'purpose' => PromptTemplate::PURPOSE_HUMANIZATION,
                'system_prompt' => 'You are a senior brand copy editor for {{brand_name}}. Refine the draft to sound natural, human, and authentic to the brand voice ({{brand_tone}}). Remove generic AI marketing cliches, vary sentence rhythm, respect forbidden words ({{words_to_avoid}}), and strictly preserve every factual claim, number, quote, date, and URL without fabricating experiences.',
                'user_prompt_template' => "Original Source Title: {{content_title}}\nOriginal Source Facts: {{content_summary}}\nPlatform: {{platform_name}}\nDraft Copy:\n{{draft_text}}\n\nStrict Factual Preservation Mode: {{strict_facts}}\n\nRefine this copy for natural flow and brand consistency.",
                'version' => 1,
                'status' => PromptTemplate::STATUS_ACTIVE,
            ],
            [
                'name' => 'Multi-Platform Adaptation',
                'slug' => 'default-platform-adaptation',
                'purpose' => PromptTemplate::PURPOSE_PLATFORM_ADAPTATION,
                'system_prompt' => 'Adapt the social post specifically for {{platform_name}} conventions while maintaining {{brand_name}} voice ({{brand_tone}}). Respect character limit ({{max_length}}), hashtag conventions ({{hashtag_style}}), and CTA style ({{cta_style}}).',
                'user_prompt_template' => "Base Copy:\n{{draft_text}}\nTarget Platform: {{platform_name}}\nMax Length: {{max_length}}\nLink URL: {{source_url}}",
                'version' => 1,
                'status' => PromptTemplate::STATUS_ACTIVE,
            ],
            [
                'name' => 'News Factual Integrity Check',
                'slug' => 'news-fact-check-assistance',
                'purpose' => PromptTemplate::PURPOSE_FACT_CHECK,
                'system_prompt' => 'Verify that the social copy does not introduce unverified claims, altered numbers, sensationalized certainty, or fabricated quotes compared to the source text.',
                'user_prompt_template' => "Source Material:\n{{content_body}}\n\nGenerated Social Copy:\n{{draft_text}}\n\nVerify factual fidelity.",
                'version' => 1,
                'status' => PromptTemplate::STATUS_ACTIVE,
            ],
        ];

        foreach ($prompts as $prompt) {
            PromptTemplate::query()->updateOrCreate(
                ['slug' => $prompt['slug']],
                $prompt
            );
        }
    }
}
