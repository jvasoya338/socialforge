<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\BrandSetting;
use App\Models\BrandVoiceExample;
use App\Models\BrandVoiceSetting;
use App\Models\ContentSource;
use App\Models\SocialAccount;
use App\Models\SocialPlatform;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            [
                'brand' => [
                    'name' => 'Gamozy',
                    'slug' => 'gamozy',
                    'website_url' => 'https://gamozy.com',
                    'description' => 'Instant browser gaming platform featuring arcade, puzzle, strategy, and multiplayer web games.',
                    'timezone' => 'UTC',
                    'default_language' => 'en',
                    'status' => Brand::STATUS_ACTIVE,
                    'settings' => [
                        'category' => 'gaming',
                        'content_focus' => 'game_releases_and_challenges',
                    ],
                ],
                'settings' => [
                    'default_timezone' => 'UTC',
                    'default_language' => 'en',
                    'approval_required' => true,
                    'auto_publish_approved' => false,
                    'require_strict_factual_check' => false,
                    'default_hashtags' => ['#Gamozy', '#BrowserGaming', '#WebGames', '#PlayInstantly'],
                    'posting_preferences' => [
                        'preferred_times' => ['14:00', '19:00'],
                        'max_posts_per_day' => 4,
                    ],
                    'ai_preferences' => [
                        'auto_humanize' => true,
                        'creativity' => 'medium_high',
                    ],
                    'url_tracking_settings' => [
                        'utm_source' => 'socialforge',
                        'utm_medium' => 'social',
                        'utm_campaign' => 'gamozy_launch',
                    ],
                ],
                'voice' => [
                    'tone' => 'energetic, gaming-focused, conversational, challenge-oriented, fun',
                    'writing_style' => 'Punchy, action-driven, player-first sentences that highlight instant gameplay without downloads.',
                    'audience' => 'Browser gamers, arcade fans, and casual players looking for quick competition.',
                    'emoji_style' => 'moderate',
                    'cta_style' => 'challenge-oriented',
                    'hashtag_style' => 'gaming-focused',
                    'default_language' => 'en',
                    'words_to_avoid' => ['delve', 'synergy', 'paradigm', 'tedious', 'install'],
                    'phrases_to_avoid' => [
                        'in today\'s fast-paced digital world',
                        'unlock your gaming potential',
                        'game-changing experience',
                    ],
                    'preferred_phrases' => [
                        'Play instantly in your browser',
                        'Think you can beat the top score?',
                        'Zero downloads. Pure gameplay.',
                    ],
                    'additional_instructions' => 'Keep copy exciting and direct. Emphasize mechanics, high-score challenges, and instant browser play.',
                    'preserve_strict_facts' => false,
                ],
                'examples' => [
                    'Neon Drift X just landed on Gamozy! Master tight hairpin turns at 200 MPH straight from your browser—no downloads needed. Think you can beat a 1:14 lap time?',
                ],
                'source' => [
                    'name' => 'Gamozy Game Catalog API',
                    'type' => ContentSource::TYPE_API,
                    'base_url' => 'https://gamozy.com/api/games',
                ],
            ],
            [
                'brand' => [
                    'name' => 'NewsCrestle',
                    'slug' => 'newscrestle',
                    'website_url' => 'https://newscrestle.com',
                    'description' => 'Independent news and journalism platform covering global affairs, technology, economy, and science.',
                    'timezone' => 'America/New_York',
                    'default_language' => 'en',
                    'status' => Brand::STATUS_ACTIVE,
                    'settings' => [
                        'category' => 'news',
                        'content_focus' => 'verified_journalism',
                        'require_strict_factual_check' => true,
                    ],
                ],
                'settings' => [
                    'default_timezone' => 'America/New_York',
                    'default_language' => 'en',
                    'approval_required' => true,
                    'auto_publish_approved' => false,
                    'require_strict_factual_check' => true,
                    'default_hashtags' => ['#NewsCrestle', '#NewsUpdate', '#WorldBriefing'],
                    'posting_preferences' => [
                        'preferred_times' => ['08:00', '12:30', '17:30'],
                        'max_posts_per_day' => 8,
                    ],
                    'ai_preferences' => [
                        'auto_humanize' => true,
                        'creativity' => 'low_factual',
                    ],
                    'url_tracking_settings' => [
                        'utm_source' => 'socialforge',
                        'utm_medium' => 'social',
                        'utm_campaign' => 'newscrestle_editorial',
                    ],
                ],
                'voice' => [
                    'tone' => 'neutral, factual, concise, clear, non-sensational',
                    'writing_style' => 'Objective journalistic reporting that preserves exact names, dates, numbers, locations, and attributions.',
                    'audience' => 'Policy followers, professionals, and general readers seeking verified reporting.',
                    'emoji_style' => 'none',
                    'cta_style' => 'informative',
                    'hashtag_style' => 'minimal',
                    'default_language' => 'en',
                    'words_to_avoid' => ['shocking', 'unbelievable', 'destroys', 'slams', 'miracle', 'jaw-dropping'],
                    'phrases_to_avoid' => [
                        'you won\'t believe what happened next',
                        'the internet is losing its mind',
                        'everything just changed forever',
                    ],
                    'preferred_phrases' => [
                        'Read the full report:',
                        'According to official filings,',
                        'Key takeaways from the report:',
                    ],
                    'additional_instructions' => 'STRICT NEWS SAFETY: Never invent facts, numbers, quotes, or dates. Never turn allegations or projections into certainty. Never sensationalize headlines.',
                    'preserve_strict_facts' => true,
                ],
                'examples' => [
                    'Central bank policymakers held benchmark interest rates steady at 4.25% on Wednesday, citing moderating inflation across core manufacturing sectors. Read the full report:',
                ],
                'source' => [
                    'name' => 'NewsCrestle Editorial Wire API',
                    'type' => ContentSource::TYPE_API,
                    'base_url' => 'https://newscrestle.com/api/articles',
                ],
            ],
            [
                'brand' => [
                    'name' => 'GildedVow',
                    'slug' => 'gildedvow',
                    'website_url' => 'https://gildedvow.com',
                    'description' => 'Fine bridal and heirloom jewelry brand crafting ethically sourced gold, diamond, and gemstone pieces.',
                    'timezone' => 'Europe/London',
                    'default_language' => 'en',
                    'status' => Brand::STATUS_ACTIVE,
                    'settings' => [
                        'category' => 'jewelry_ecommerce',
                        'content_focus' => 'collections_and_craftsmanship',
                    ],
                ],
                'settings' => [
                    'default_timezone' => 'Europe/London',
                    'default_language' => 'en',
                    'approval_required' => true,
                    'auto_publish_approved' => false,
                    'require_strict_factual_check' => false,
                    'default_hashtags' => ['#GildedVow', '#FineJewelry', '#HeirloomCraft', '#TimelessDesign'],
                    'posting_preferences' => [
                        'preferred_times' => ['11:00', '18:00'],
                        'max_posts_per_day' => 2,
                    ],
                    'ai_preferences' => [
                        'auto_humanize' => true,
                        'creativity' => 'refined',
                    ],
                    'url_tracking_settings' => [
                        'utm_source' => 'socialforge',
                        'utm_medium' => 'social',
                        'utm_campaign' => 'gildedvow_collection',
                    ],
                ],
                'voice' => [
                    'tone' => 'elegant, premium, emotional, minimal, trust-focused',
                    'writing_style' => 'Warm, understated luxury storytelling focused on artisan detail, provenance, and meaningful milestones.',
                    'audience' => 'Couples, bridal shoppers, and collectors of timeless fine jewelry.',
                    'emoji_style' => 'minimal',
                    'cta_style' => 'inviting',
                    'hashtag_style' => 'curated',
                    'default_language' => 'en',
                    'words_to_avoid' => ['cheap', 'bargain', 'clearance', 'hype', 'delve', 'flashy'],
                    'phrases_to_avoid' => [
                        'buy now before stock runs out',
                        'rock bottom prices',
                        'ultimate luxury hack',
                    ],
                    'preferred_phrases' => [
                        'Crafted to be cherished for generations',
                        'Explore the collection',
                        'Hand-finished in 18k recycled gold',
                    ],
                    'additional_instructions' => 'Highlight material authenticity, ethical sourcing, and quiet sophistication. Avoid aggressive sales urgency or fabricated customer stories.',
                    'preserve_strict_facts' => false,
                ],
                'examples' => [
                    'Hand-set in 18k recycled yellow gold, the Aurelia Solitaire captures natural light from every angle. Designed for moments meant to endure. Explore the collection:',
                ],
                'source' => [
                    'name' => 'GildedVow Catalog Webhook/API',
                    'type' => ContentSource::TYPE_API,
                    'base_url' => 'https://gildedvow.com/api/products',
                ],
            ],
        ];

        $platforms = SocialPlatform::all();

        foreach ($brands as $entry) {
            $brand = Brand::query()->updateOrCreate(
                ['slug' => $entry['brand']['slug']],
                $entry['brand']
            );

            BrandSetting::query()->updateOrCreate(
                ['brand_id' => $brand->id],
                $entry['settings']
            );

            BrandVoiceSetting::query()->updateOrCreate(
                ['brand_id' => $brand->id],
                $entry['voice']
            );

            foreach ($entry['examples'] as $exampleText) {
                BrandVoiceExample::query()->firstOrCreate(
                    [
                        'brand_id' => $brand->id,
                        'content' => $exampleText,
                    ],
                    [
                        'source' => 'seeded_curated',
                        'approved' => true,
                    ]
                );
            }

            ContentSource::query()->firstOrCreate(
                [
                    'brand_id' => $brand->id,
                    'name' => $entry['source']['name'],
                ],
                [
                    'type' => $entry['source']['type'],
                    'base_url' => $entry['source']['base_url'],
                    'authentication_type' => 'api_key',
                    'status' => ContentSource::STATUS_ACTIVE,
                ]
            );

            // Create unconnected social account slots for primary platforms (NO fake credentials)
            foreach ($platforms->whereIn('slug', ['facebook', 'instagram', 'x']) as $platform) {
                SocialAccount::query()->firstOrCreate(
                    [
                        'brand_id' => $brand->id,
                        'social_platform_id' => $platform->id,
                    ],
                    [
                        'account_name' => $brand->name.' '.$platform->name,
                        'username' => strtolower($brand->slug),
                        'account_type' => 'official_profile',
                        'status' => SocialAccount::STATUS_ACTIVE,
                        'connection_status' => SocialAccount::CONNECTION_NOT_CONNECTED,
                    ]
                );
            }
        }
    }
}
