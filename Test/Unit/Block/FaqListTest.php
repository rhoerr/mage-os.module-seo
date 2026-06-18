<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Seo\Api\FaqCollectorInterface;
use MageOS\Seo\Block\Widget\FaqList;
use MageOS\Seo\Model\Faq\SourcePool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the AbstractFaqElement resolve/collect flow through the concrete FaqList widget.
 */
class FaqListTest extends TestCase
{
    /**
     * @var SourcePool&MockObject
     */
    private SourcePool&MockObject $sourcePool;

    /**
     * @var FaqCollectorInterface&MockObject
     */
    private FaqCollectorInterface&MockObject $collector;

    /**
     * @var FaqList
     */
    private FaqList $block;

    protected function setUp(): void
    {
        $this->sourcePool = $this->createMock(SourcePool::class);
        $this->collector  = $this->createMock(FaqCollectorInterface::class);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $store        = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $storeManager->method('getStore')->willReturn($store);

        $this->block = new FaqList(
            $this->createMock(Context::class),
            $this->sourcePool,
            $this->collector,
            $storeManager
        );
    }

    public function testResolvesFaqsAndCollectsIdentifier(): void
    {
        $faqs = [['question' => 'Q', 'answer' => 'A']];
        $this->block->setData('identifier', 'shipping');
        $this->collector->expects($this->once())->method('collect')->with('shipping');
        $this->sourcePool->method('getFaqs')->with('shipping', 1)->willReturn($faqs);

        $this->assertSame($faqs, $this->block->getFaqs());
    }

    public function testEmptyIdentifierReturnsEmptyAndDoesNotCollect(): void
    {
        $this->block->setData('identifier', '');
        $this->collector->expects($this->never())->method('collect');
        $this->assertSame([], $this->block->getFaqs());
    }

    public function testResolutionIsMemoised(): void
    {
        $this->block->setData('identifier', 'shipping');
        $this->sourcePool->expects($this->once())->method('getFaqs')->willReturn([
            ['question' => 'Q', 'answer' => 'A'],
        ]);
        $this->block->getFaqs();
        $this->block->getFaqs();
    }
}
