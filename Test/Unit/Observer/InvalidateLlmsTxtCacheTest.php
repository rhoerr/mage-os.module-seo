<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Observer;

use Magento\Framework\Event\Observer;
use MageOS\Seo\Model\Feed\FeedInvalidator;
use MageOS\Seo\Observer\InvalidateLlmsTxtCache;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InvalidateLlmsTxtCacheTest extends TestCase
{
    /**
     * @var FeedInvalidator&MockObject
     */
    private FeedInvalidator&MockObject $feedInvalidator;

    private InvalidateLlmsTxtCache $observer;

    protected function setUp(): void
    {
        $this->feedInvalidator = $this->createMock(FeedInvalidator::class);
        $this->observer        = new InvalidateLlmsTxtCache($this->feedInvalidator);
    }

    public function testExecuteInvalidatesLlmsFeeds(): void
    {
        $this->feedInvalidator->expects($this->once())->method('invalidateLlms');
        $this->observer->execute($this->createMock(Observer::class));
    }
}
