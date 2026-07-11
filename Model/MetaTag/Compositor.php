<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\MetaTag;

use Magento\Framework\View\LayoutInterface;
use MageOS\Seo\Api\MetaTagProviderInterface;
use MageOS\Seo\Model\Pool\HandleMatcher;

class Compositor
{
    /**
     * @param LayoutInterface $layout
     * @param HandleMatcher $handleMatcher
     * @param array<mixed> $providers
     */
    public function __construct(
        private readonly LayoutInterface        $layout,
        private readonly HandleMatcher $handleMatcher,
        private readonly array         $providers = []
    ) {
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
