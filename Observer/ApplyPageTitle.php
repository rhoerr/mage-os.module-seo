<?php

declare(strict_types=1);

namespace MageOS\Seo\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Page\Config as PageConfig;
use MageOS\Seo\Model\PageTitle\Compositor;

/**
 * Applies the resolved page title from the PageTitle compositor to the current page.
 *
 * The built-in providers only speak when an explicit title exists (variant title,
 * meta_title), so core behaviour is untouched by default; the observer is what makes
 * the compositor a real extension point for bridge modules.
 */
class ApplyPageTitle implements ObserverInterface
{
    /**
     * @param Compositor $compositor
     * @param PageConfig $pageConfig
     */
    public function __construct(
        private readonly Compositor $compositor,
        private readonly PageConfig $pageConfig
    ) {
    }

    /**
     * Apply the winning page title to the current frontend page.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        try {
            $title = $this->compositor->getTitle();
            if ($title !== '') {
                $this->pageConfig->getTitle()->set($title);
            }
        } catch (\Exception) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch -- never break rendering
        }
    }
}
