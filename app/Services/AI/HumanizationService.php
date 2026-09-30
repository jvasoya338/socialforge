<?php

namespace App\Services\AI;

use App\Models\Brand;
use App\Models\Content;
use App\Models\SocialPlatform;
use Illuminate\Support\Str;

class HumanizationService
{
    public const GENERIC_AI_PHRASES = [
        'in today\'s digital landscape' => '',
        'in today\'s fast-paced digital world' => '',
        'in today\'s fast-paced world' => '',
        'let\'s delve into' => 'let\'s look at',
        'delve into' => 'explore',
        'delve' => 'explore',
        'a testament to' => 'proof of',
        'rich tapestry of' => '',
        'game-changing experience' => 'fresh experience',
        'game-changer' => 'standout',
        'unlock the potential of' => 'use',
        'unlock your gaming potential' => 'test your skills',
        'elevate your experience' => 'enjoy more',
        'without further ado' => '',
        'look no further' => '',
        'it is important to note that' => '',
        'moreover,' => 'also,',
        'furthermore,' => 'additionally,',
    ];

    public const SENSATIONAL_NEWS_TERMS = [
        'shocking',
        'unbelievable',
        'jaw-dropping',
        'mind-blowing',
        'miracle',
        'you won\'t believe',
        'the internet is losing its mind',
        'everything just changed forever',
    ];

    /**
     * Refine an AI draft for natural flow, brand voice consistency, and factual safety.
     */
    public function humanize(
        string $draft,
        Brand $brand,
        ?Content $sourceContent = null,
        ?SocialPlatform $platform = null
    ): string {
        $brand->loadMissing(['brandVoiceSetting', 'brandSetting']);
        $voice = $brand->brandVoiceSetting;
        $text = trim($draft);

        // 1. Replace generic AI marketing phrases
        foreach (self::GENERIC_AI_PHRASES as $cliche => $replacement) {
            $text = str_ireplace($cliche, $replacement, $text);
        }

        // 2. Remove brand-configured phrases_to_avoid and words_to_avoid
        if ($voice) {
            foreach ($voice->phrases_to_avoid ?? [] as $phrase) {
                if (is_string($phrase) && $phrase !== '') {
                    $text = str_ireplace($phrase, '', $text);
                }
            }

            foreach ($voice->words_to_avoid ?? [] as $word) {
                if (is_string($word) && $word !== '') {
                    $pattern = '/\b'.preg_quote($word, '/').'\b/i';
                    $text = (string) preg_replace($pattern, '', $text);
                }
            }
        }

        // 3. Strict factual & news safety checks (e.g., NewsCrestle)
        if ($brand->requiresStrictFactualCheck()) {
            foreach (self::SENSATIONAL_NEWS_TERMS as $term) {
                $text = str_ireplace($term, '', $text);
            }

            // Replace exclamation marks with neutral periods for objective news tone
            $text = (string) preg_replace('/!+/', '.', $text);

            // If source content contains uncertainty qualifiers (allegedly, reportedly, projected),
            // ensure certainty claims ("definitely proved", "undeniably") are stripped
            $text = str_ireplace(['definitely proved', 'undeniably confirmed', '100% guaranteed'], 'reported', $text);
        }

        // 4. Emoji style enforcement
        $emojiStyle = $voice?->emoji_style ?? 'minimal';
        if ($emojiStyle === 'none') {
            $text = (string) preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', $text);
        }

        // 5. Normalize whitespace and punctuation artifacts
        $text = (string) preg_replace('/\s{2,}/', ' ', $text);
        $text = (string) preg_replace('/\s+([,\.\!\?])/', '$1', $text);
        $text = (string) preg_replace('/\.{2,}(?!\.)/', '.', $text);
        $text = trim($text, " \t\n\r\0\x0B,-");

        // 6. Enforce platform max length if applicable
        if ($platform && $platform->max_text_length) {
            $maxBody = max(60, $platform->max_text_length - 40);
            if (mb_strlen($text) > $maxBody) {
                $text = Str::limit($text, $maxBody - 3, '...');
            }
        }

        return trim($text);
    }
}
