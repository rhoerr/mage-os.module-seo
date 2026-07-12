<?php

declare(strict_types=1);

namespace MageOS\Seo\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use MageOS\Seo\Model\Feed\FeedInvalidator;

class InvalidateLlmsTxtCache implements ObserverInterface
{
    /**
     * @param FeedInvalidator $feedInvalidator
     */
    public function __construct(
        private readonly FeedInvalidator $feedInvalidator
    ) {
    }

    /**
     * Invalidate the llms.txt / llms-full.txt feeds when their source data changes:
     * the pre-generated files are deleted and the cached documents are purged from
     * built-in FPC or Varnish so they are regenerated promptly.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $this->feedInvalidator->invalidateLlms();
    }
}
