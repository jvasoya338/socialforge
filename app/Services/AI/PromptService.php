<?php

namespace App\Services\AI;

use App\Models\Brand;
use App\Models\Content;
use App\Models\PromptTemplate;
use App\Models\SocialPlatform;

class PromptService
{
    public function getActiveTemplate(string $purpose): ?PromptTemplate
    {
        return PromptTemplate::query()
            ->where('purpose', $purpose)
            ->where('status', PromptTemplate::STATUS_ACTIVE)
            ->orderByDesc('version')
            ->first();
    }

    /**
     * Render system and user prompts for a brand, content item, and platform.
     *
     * @return array{template: ?PromptTemplate, version: int, system_prompt: string, user_prompt: string, variables: array<string, mixed>}
     */
    public function buildPrompts(
        string $purpose,
        Brand $brand,
        ?Content $content = null,
        ?SocialPlatform $platform = null,
        string $draftText = ''
    ): array {
        $brand->loadMissing(['brandVoiceSetting', 'brandVoiceExamples']);
        $voice = $brand->brandVoiceSetting;
        $template = $this->getActiveTemplate($purpose);

        $approvedExamples = $brand->brandVoiceExamples
            ->where('approved', true)
            ->when($platform, fn ($c) => $c->filter(fn ($ex) => $ex->social_platform_id === null || $ex->social_platform_id === $platform->id))
            ->take(3)
            ->pluck('content')
            ->implode("\n- ");

        $variables = [
            'brand_name' => $brand->name,
            'brand_tone' => $voice?->tone ?? 'professional, clear',
            'brand_style' => $voice?->writing_style ?? 'concise and engaging',
            'brand_audience' => $voice?->audience ?? 'general audience',
            'emoji_style' => $voice?->emoji_style ?? 'minimal',
            'cta_style' => $voice?->cta_style ?? 'subtle',
            'hashtag_style' => $voice?->hashtag_style ?? 'moderate',
            'words_to_avoid' => implode(', ', $voice?->words_to_avoid ?? []),
            'phrases_to_avoid' => implode('; ', $voice?->phrases_to_avoid ?? []),
            'preferred_phrases' => $voice?->preferred_phrases ?? [],
            'additional_instructions' => $voice?->additional_instructions ?? '',
            'strict_facts' => $brand->requiresStrictFactualCheck() ? 'YES' : 'NO',
            'voice_examples' => $approvedExamples !== '' ? "- {$approvedExamples}" : 'None provided.',
            'content_title' => $content?->title ?? '',
            'content_type' => $content?->type ?? 'general',
            'content_summary' => $content?->summary ?? '',
            'content_body' => $content?->body ?? '',
            'source_url' => $content?->source_url ?? '',
            'platform_name' => $platform?->name ?? 'General Social',
            'platform_slug' => $platform?->slug ?? 'facebook',
            'max_length' => $platform?->max_text_length ?? 2000,
            'draft_text' => $draftText,
        ];

        $systemPrompt = $template?->system_prompt ?? 'You are the brand strategist for {{brand_name}}. Tone: {{brand_tone}}.';
        $userPrompt = $template?->user_prompt_template ?? 'Title: {{content_title}} Summary: {{content_summary}} Platform: {{platform_name}}';

        foreach ($variables as $key => $val) {
            $replacement = is_array($val) ? implode(', ', $val) : (string) $val;
            $systemPrompt = str_replace('{{'.$key.'}}', $replacement, $systemPrompt);
            $userPrompt = str_replace('{{'.$key.'}}', $replacement, $userPrompt);
        }

        return [
            'template' => $template,
            'version' => $template?->version ?? 1,
            'system_prompt' => $systemPrompt,
            'user_prompt' => $userPrompt,
            'variables' => $variables,
        ];
    }
}
