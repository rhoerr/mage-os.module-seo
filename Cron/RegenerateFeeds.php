<?php

declare(strict_types=1);

namespace MageOS\Seo\Cron;

use MageOS\Seo\Model\Feed\FeedRegenerator;

/**
 * Nightly full rebuild of all pre-generated SEO feeds.
 *
 * Safety net alongside the event-driven queue consumer: catches drift from
 * changes that carry no invalidation event (config edits, imports, URL suffix
 * changes) and re-creates files lost to deployments or cache clears.
 */
class RegenerateFeeds
{
    /**
     * @param FeedRegenerator $feedRegenerator
     */
    public function __construct(
        private readonly FeedRegenerator $feedRegenerator
    ) {
    }

    /**
     * Regenerate every enabled feed for every active store view.
     *
     * @return void
     */
    public function execute(): void
    {
        $this->feedRegenerator->regenerate();
    }
}
