<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\PageTitle;

use Magento\Framework\View\Layout;
use MageOS\Seo\Api\PageTitleProviderInterface;
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
