<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\MetaTag;

use Magento\Framework\View\Layout;
use MageOS\Seo\Api\MetaTagProviderInterface;
use MageOS\Seo\Model\Pool\HandleMatcher;

class Compositor
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
     * Collect all meta tag definitions from matching providers.
     *
     * @return mixed[]
     */
    public function getMetaTags(): array
    {
        $activeHandles = $this->layout->getUpdate()->getHandles();
        $tags = [];

        foreach ($this->providers as $provider) {
            if (!$provider instanceof MetaTagProviderInterface) {
                continue;
            }
            if (!$this->handleMatcher->matches($provider->getHandles(), $activeHandles)) {
                continue;
            }
            foreach ($provider->getMetaTags() as $tag) {
                if (!empty($tag['content'])) {
                    $tags[] = $tag;
                }
            }
        }

        return $tags;
    }
}
