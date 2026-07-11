<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Feed;

use Magento\Framework\FlagManager;
use Magento\Framework\MessageQueue\PublisherInterface;
use Psr\Log\LoggerInterface;

/**
 * Queues a feed-group rebuild, collapsing duplicate requests.
 *
 * A burst of invalidations (a catalog import saving thousands of products, for
 * example) must not queue thousands of identical builds: a pending flag per feed
 * group ensures at most one message is queued until the consumer starts working,
 * and the consumer clears the flag *before* building so changes arriving during a
 * build queue exactly one follow-up rebuild.
 */
class RegenerationRequester
{
    public const TOPIC = 'mageos.seo.feed.regenerate';

    private const FLAG_PREFIX = 'mageos_seo_feed_pending_';

    /**
     * @param FlagManager $flagManager
     * @param PublisherInterface $publisher
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly FlagManager        $flagManager,
        private readonly PublisherInterface $publisher,
        private readonly LoggerInterface    $logger
    ) {
    }

    /**
     * Queue a rebuild of the given feed group unless one is already pending.
     *
     * Best effort: a queue/flag failure is logged, never thrown — feed freshness
     * must not break saves or frontend requests (the nightly cron is the backstop).
     *
     * @param string $group One of FeedRegenerator::GROUPS
     * @return void
     */
    public function request(string $group): void
    {
        try {
            if ($this->flagManager->getFlagData(self::FLAG_PREFIX . $group)) {
                return;
            }
            $this->flagManager->saveFlag(self::FLAG_PREFIX . $group, time());
            $this->publisher->publish(self::TOPIC, $group);
        } catch (\Throwable $e) {
            $this->logger->error(
                'MageOS_Seo: could not queue feed regeneration: ' . $e->getMessage(),
                ['exception' => $e, 'group' => $group]
            );
        }
    }

    /**
     * Mark a queued request as picked up so later invalidations queue a fresh build.
     *
     * Called by the consumer before it starts building.
     *
     * @param string $group
     * @return void
     */
    public function acknowledge(string $group): void
    {
        try {
            $this->flagManager->deleteFlag(self::FLAG_PREFIX . $group);
        } catch (\Throwable $e) {
            $this->logger->error(
                'MageOS_Seo: could not clear feed regeneration flag: ' . $e->getMessage(),
                ['exception' => $e, 'group' => $group]
            );
        }
    }
}
