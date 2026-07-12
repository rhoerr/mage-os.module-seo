<?php

declare(strict_types=1);

namespace MageOS\Seo\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MageOS\Seo\Model\Feed\FeedInvalidator;

/**
 * Invalidates only the /llms.jsonl feed when a product changes.
 *
 * Kept separate from the llms.txt/llms-full.txt invalidation so a product save does not
 * needlessly regenerate the narrative documents (which depend on org/category data, not products).
 */
class InvalidateLlmsJsonlCache implements ObserverInterface
{
    /**
     * @param FeedInvalidator $feedInvalidator
     */
    public function __construct(
        private readonly FeedInvalidator $feedInvalidator
    ) {
    }

    /**
     * Invalidate the llms.jsonl feed file and cached responses on product save.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $this->feedInvalidator->invalidateJsonl();
    }
}
