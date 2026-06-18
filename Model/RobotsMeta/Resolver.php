<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\RobotsMeta;

use Magento\Framework\View\Layout;
use MageOS\Seo\Api\RobotsMetaProviderInterface;
use MageOS\Seo\Model\Pool\HandleMatcher;

/**
 * First-wins resolver for the robots meta provider pool.
 *
 * Iterates every registered provider whose handles match the current page and returns the value
 * from the highest-sortOrder provider that yields a non-empty robots string. Returns null when no
 * provider has an opinion, leaving Magento's default robots behaviour untouched.
 */
class Resolver
{
    /**
     * @var HandleMatcher
     */
    private readonly HandleMatcher $handleMatcher;

    /**
     * @param Layout $layout
     * @param array<mixed> $providers
     * @param HandleMatcher|null $handleMatcher
     */
    public function __construct(
        private readonly Layout $layout,
        private readonly array  $providers = [],
        ?HandleMatcher $handleMatcher = null
    ) {
        $this->handleMatcher = $handleMatcher ?? new HandleMatcher();
    }

    /**
     * Resolve the winning robots meta value for the current page, or null if none applies.
     *
     * @param int $storeId
     * @return string|null
     */
    public function resolve(int $storeId): ?string
    {
        $activeHandles = $this->layout->getUpdate()->getHandles();

        $candidates = [];
        foreach ($this->providers as $provider) {
            if (!$provider instanceof RobotsMetaProviderInterface) {
                continue;
            }
            if (!$this->handleMatcher->matches($provider->getHandles(), $activeHandles)) {
                continue;
            }
            $robots = $provider->getRobots($storeId);
            if ($robots !== null && $robots !== '') {
                $candidates[] = ['robots' => $robots, 'sort' => $provider->getSortOrder()];
            }
        }

        if (empty($candidates)) {
            return null;
        }

        usort($candidates, static fn (array $a, array $b): int => $b['sort'] <=> $a['sort']);

        return $candidates[0]['robots'];
    }
}
