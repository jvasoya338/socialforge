<?php

namespace App\Services\Publishing;

use App\Exceptions\SocialPublishException;
use App\Models\PublishingAttempt;
use App\Services\Publishing\Contracts\SocialPublisherInterface;
use App\Services\Publishing\Publishers\FacebookPublisher;
use App\Services\Publishing\Publishers\InstagramPublisher;
use App\Services\Publishing\Publishers\LinkedInPublisher;
use App\Services\Publishing\Publishers\PinterestPublisher;
use App\Services\Publishing\Publishers\XPublisher;
use App\Services\Publishing\Publishers\YouTubePublisher;

class SocialPublisherManager
{
    /** @var array<string, SocialPublisherInterface> */
    protected array $publishers = [];

    public function __construct()
    {
        $this->register(new FacebookPublisher);
        $this->register(new InstagramPublisher);
        $this->register(new XPublisher);
        $this->register(new PinterestPublisher);
        $this->register(new YouTubePublisher);
        $this->register(new LinkedInPublisher);
    }

    public function register(SocialPublisherInterface $publisher): void
    {
        $this->publishers[$publisher->platformSlug()] = $publisher;
    }

    public function publisher(string $platformSlug): SocialPublisherInterface
    {
        $normalized = strtolower($platformSlug);

        if (! isset($this->publishers[$normalized])) {
            throw new SocialPublishException(
                "No social publisher registered for platform '{$platformSlug}'.",
                failureCategory: PublishingAttempt::CATEGORY_VALIDATION,
            );
        }

        return $this->publishers[$normalized];
    }
}
