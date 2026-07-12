<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Feed;

use Psr\Log\LoggerInterface;

/**
 * Queue consumer that rebuilds one feed group after an invalidation.
 *
 * Runs serially per queue, so concurrent whole-catalog builds are structurally
 * impossible regardless of how many web workers or hosts are serving traffic.
 */
class RegenerateConsumer
{
    /**
     * @param FeedRegenerator $feedRegenerator
     * @param RegenerationRequester $regenerationRequester
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly FeedRegenerator       $feedRegenerator,
        private readonly RegenerationRequester $regenerationRequester,
        private readonly LoggerInterface       $logger
    ) {
    }

    /**
     * Rebuild the requested feed group for all stores.
     *
     * @param string $group One of FeedRegenerator::GROUPS
     * @return void
     */
    public function process(string $group): void
    {
        if (!\in_array($group, FeedRegenerator::GROUPS, true)) {
            $this->logger->warning('MageOS_Seo: unknown feed group in regeneration queue: ' . $group);
            return;
        }

        // Clear the pending flag BEFORE building: invalidations arriving while we
        // build must queue exactly one follow-up rebuild, not be lost.
        $this->regenerationRequester->acknowledge($group);

        $this->feedRegenerator->regenerate($group);
    }
}
