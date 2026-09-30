<?php

namespace Database\Seeders;

use App\Models\SocialPlatform;
use Illuminate\Database\Seeder;

class SocialPlatformSeeder extends Seeder
{
    public function run(): void
    {
        $platforms = [
            [
                'name' => 'Facebook',
                'slug' => 'facebook',
                'icon' => 'fab fa-facebook-f',
                'status' => SocialPlatform::STATUS_ACTIVE,
                'supports_text' => true,
                'supports_image' => true,
                'supports_video' => true,
                'supports_reels' => true,
                'supports_stories' => true,
                'supports_analytics' => true,
                'supports_scheduling' => true,
                'max_text_length' => 63206,
                'configuration' => [
                    'api_version' => 'v20.0',
                    'requires_page_id' => true,
                    'optimal_hashtag_count' => 3,
                ],
            ],
            [
                'name' => 'Instagram',
                'slug' => 'instagram',
                'icon' => 'fab fa-instagram',
                'status' => SocialPlatform::STATUS_ACTIVE,
                'supports_text' => false,
                'supports_image' => true,
                'supports_video' => true,
                'supports_reels' => true,
                'supports_stories' => true,
                'supports_analytics' => true,
                'supports_scheduling' => true,
                'max_text_length' => 2200,
                'configuration' => [
                    'requires_media' => true,
                    'optimal_hashtag_count' => 8,
                    'link_in_bio_preferred' => true,
                ],
            ],
            [
                'name' => 'X',
                'slug' => 'x',
                'icon' => 'fab fa-x-twitter',
                'status' => SocialPlatform::STATUS_ACTIVE,
                'supports_text' => true,
                'supports_image' => true,
                'supports_video' => true,
                'supports_reels' => false,
                'supports_stories' => false,
                'supports_analytics' => true,
                'supports_scheduling' => true,
                'max_text_length' => 280,
                'configuration' => [
                    'api_version' => '2',
                    'optimal_hashtag_count' => 2,
                ],
            ],
            [
                'name' => 'Pinterest',
                'slug' => 'pinterest',
                'icon' => 'fab fa-pinterest-p',
                'status' => SocialPlatform::STATUS_ACTIVE,
                'supports_text' => false,
                'supports_image' => true,
                'supports_video' => true,
                'supports_reels' => false,
                'supports_stories' => false,
                'supports_analytics' => true,
                'supports_scheduling' => true,
                'max_text_length' => 500,
                'configuration' => [
                    'requires_media' => true,
                    'requires_board_id' => true,
                    'optimal_hashtag_count' => 4,
                ],
            ],
            [
                'name' => 'YouTube',
                'slug' => 'youtube',
                'icon' => 'fab fa-youtube',
                'status' => SocialPlatform::STATUS_ACTIVE,
                'supports_text' => false,
                'supports_image' => false,
                'supports_video' => true,
                'supports_reels' => true,
                'supports_stories' => false,
                'supports_analytics' => true,
                'supports_scheduling' => true,
                'max_text_length' => 5000,
                'configuration' => [
                    'requires_video' => true,
                    'optimal_hashtag_count' => 5,
                ],
            ],
            [
                'name' => 'LinkedIn',
                'slug' => 'linkedin',
                'icon' => 'fab fa-linkedin-in',
                'status' => SocialPlatform::STATUS_ACTIVE,
                'supports_text' => true,
                'supports_image' => true,
                'supports_video' => true,
                'supports_reels' => false,
                'supports_stories' => false,
                'supports_analytics' => true,
                'supports_scheduling' => true,
                'max_text_length' => 3000,
                'configuration' => [
                    'optimal_hashtag_count' => 4,
                    'professional_tone' => true,
                ],
            ],
        ];

        foreach ($platforms as $platformData) {
            SocialPlatform::query()->updateOrCreate(
                ['slug' => $platformData['slug']],
                $platformData
            );
        }
    }
}
