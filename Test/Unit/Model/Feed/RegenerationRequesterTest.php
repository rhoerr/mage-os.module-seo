<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Feed;

use Magento\Framework\FlagManager;
use Magento\Framework\MessageQueue\PublisherInterface;
use MageOS\Seo\Model\Feed\FeedRegenerator;
use MageOS\Seo\Model\Feed\RegenerationRequester;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RegenerationRequesterTest extends TestCase
{
    /**
     * @var FlagManager&MockObject
     */
    private FlagManager&MockObject $flagManager;

    /**
     * @var PublisherInterface&MockObject
     */
    private PublisherInterface&MockObject $publisher;

    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface&MockObject $logger;

    private RegenerationRequester $requester;

    protected function setUp(): void
    {
        $this->flagManager = $this->createMock(FlagManager::class);
        $this->publisher   = $this->createMock(PublisherInterface::class);
        $this->logger      = $this->createMock(LoggerInterface::class);

        $this->requester = new RegenerationRequester($this->flagManager, $this->publisher, $this->logger);
    }

    public function testRequestPublishesAndSetsPendingFlag(): void
    {
        $this->flagManager->method('getFlagData')->willReturn(null);
        $this->flagManager
            ->expects($this->once())
            ->method('saveFlag')
            ->with('mageos_seo_feed_pending_llms', $this->greaterThan(0));
        $this->publisher
            ->expects($this->once())
            ->method('publish')
            ->with(RegenerationRequester::TOPIC, FeedRegenerator::GROUP_LLMS);

        $this->requester->request(FeedRegenerator::GROUP_LLMS);
    }

    public function testDuplicateRequestsAreCollapsedWhilePending(): void
    {
        // A burst of invalidations must queue at most one build per feed group.
        $this->flagManager->method('getFlagData')->willReturn(time());
        $this->flagManager->expects($this->never())->method('saveFlag');
        $this->publisher->expects($this->never())->method('publish');

        $this->requester->request(FeedRegenerator::GROUP_JSONL);
    }

    public function testPublishFailureIsLoggedNotThrown(): void
    {
        // Feed freshness must never break a save or a frontend request.
        $this->flagManager->method('getFlagData')->willReturn(null);
        $this->publisher->method('publish')->willThrowException(new \RuntimeException('queue down'));
        $this->logger->expects($this->once())->method('error');

        $this->requester->request(FeedRegenerator::GROUP_HREFLANG);
    }

    public function testAcknowledgeClearsThePendingFlag(): void
    {
        $this->flagManager
            ->expects($this->once())
            ->method('deleteFlag')
            ->with('mageos_seo_feed_pending_llms');

        $this->requester->acknowledge(FeedRegenerator::GROUP_LLMS);
    }
}
