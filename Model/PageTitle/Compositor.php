<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\PageTitle;

use Magento\Framework\View\LayoutInterface;
use MageOS\Seo\Api\PageTitleProviderInterface;
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
     * Return the winning page title, or an empty string if no provider contributes one.
     *
     * The non-empty provider with the highest sortOrder wins.
     *
     * @return string
     */
    public function getTitle(): string
    {
        $activeHandles = $this->layout->getUpdate()->getHandles();

        $candidates = [];
        foreach ($this->providers as $provider) {
            if (!$provider instanceof PageTitleProviderInterface) {
                continue;
            }
            if (!$this->handleMatcher->matches($provider->getHandles(), $activeHandles)) {
                continue;
            }
            $title = $provider->getTitle();
            if ($title !== '') {
                $candidates[] = ['title' => $title, 'sort' => $provider->getSortOrder()];
            }
        }

        if (empty($candidates)) {
            return '';
        }

        usort($candidates, static fn (array $a, array $b) => $b['sort'] <=> $a['sort']);

        return $candidates[0]['title'];
    }
}
