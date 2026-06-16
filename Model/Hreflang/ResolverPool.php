<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Hreflang;

use Magento\Framework\View\Layout;
use MageOS\Seo\Api\HreflangResolverInterface;
use MageOS\Seo\Model\Config;
use MageOS\Seo\Model\Pool\HandleMatcher;

/**
 * Builds the full hreflang link set for the current page.
 *
 * Picks the first matching resolver that returns region links, then delegates to AlternateBuilder
 * to append automatic language-only tags and the configured x-default (and to drop single-store
 * pages).
 */
class ResolverPool
{
    /**
     * @var HandleMatcher
     */
    private readonly HandleMatcher $handleMatcher;

    /**
     * @var AlternateBuilder
     */
    private readonly AlternateBuilder $alternateBuilder;

    /**
     * @param Layout $layout
     * @param Config $seoConfig
     * @param array<mixed> $resolvers
     * @param HandleMatcher|null $handleMatcher
     * @param AlternateBuilder|null $alternateBuilder
     */
    public function __construct(
        private readonly Layout $layout,
        private readonly Config $seoConfig,
        private readonly array  $resolvers = [],
        ?HandleMatcher $handleMatcher = null,
        ?AlternateBuilder $alternateBuilder = null
    ) {
        $this->handleMatcher    = $handleMatcher ?? new HandleMatcher();
        $this->alternateBuilder = $alternateBuilder ?? new AlternateBuilder($seoConfig);
    }

    /**
     * Return the full ordered hreflang link set for the current page.
     *
     * @return array<int, array{hreflang: string, url: string}>
     */
    public function getLinks(): array
    {
        return $this->alternateBuilder->build($this->resolveRegionLinks());
    }

    /**
     * Find the first matching resolver that yields region links.
     *
     * @return array<int, array{hreflang: string, url: string, store_id: int}>
     */
    private function resolveRegionLinks(): array
    {
        $activeHandles = $this->layout->getUpdate()->getHandles();

        foreach ($this->resolvers as $resolver) {
            if (!$resolver instanceof HreflangResolverInterface) {
                continue;
            }
            if (!$this->handleMatcher->matches($resolver->getHandles(), $activeHandles)) {
                continue;
            }
            $links = $resolver->getLinks();
            if (!empty($links)) {
                return $links;
            }
        }

        return [];
    }
}
