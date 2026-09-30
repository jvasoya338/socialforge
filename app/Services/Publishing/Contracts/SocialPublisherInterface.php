<?php

namespace App\Services\Publishing\Contracts;

use App\Models\SocialPost;

interface SocialPublisherInterface
{
    public function platformSlug(): string;

    public function validatePostRequirements(SocialPost $post): void;

    /**
     * Publish the post to the official social platform API.
     *
     * @return array{platform_post_id: string, platform_post_url: string, http_status: int, response_data: array<string, mixed>}
     */
    public function publish(SocialPost $post): array;
}
