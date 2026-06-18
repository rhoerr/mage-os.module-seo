<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Seo\Api\FaqCollectorInterface;
use MageOS\Seo\Block\FaqJsonLd;
use MageOS\Seo\Model\Config;
use MageOS\Seo\Model\Faq\SourcePool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FaqJsonLdTest extends TestCase
{
    /**
     * @var FaqCollectorInterface&MockObject
     */
    private FaqCollectorInterface&MockObject $collector;

    /**
     * @var SourcePool&MockObject
     */
    private SourcePool&MockObject $sourcePool;

    /**
     * @var Config&MockObject
     */
    private Config&MockObject $config;

    /**
     * @var FaqJsonLd
     */
    private FaqJsonLd $block;

    protected function setUp(): void
    {
        $this->collector  = $this->createMock(FaqCollectorInterface::class);
        $this->sourcePool = $this->createMock(SourcePool::class);
        $this->config     = $this->createMock(Config::class);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $store        = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $storeManager->method('getStore')->willReturn($store);

        $this->block = new FaqJsonLd(
            $this->createMock(Context::class),
            $this->collector,
            $this->sourcePool,
            $storeManager,
            $this->config
        );
    }

    public function testEmptyWhenStructuredDataDisabled(): void
    {
        $this->config->method('isStructuredDataEnabled')->willReturn(false);
        $this->assertSame('', $this->block->getJsonLd());
    }

    public function testEmptyWhenNoIdentifiersCollected(): void
    {
        $this->config->method('isStructuredDataEnabled')->willReturn(true);
        $this->collector->method('getIdentifiers')->willReturn([]);
        $this->assertSame('', $this->block->getJsonLd());
    }

    public function testEmptyWhenNoFaqsResolved(): void
    {
        $this->config->method('isStructuredDataEnabled')->willReturn(true);
        $this->collector->method('getIdentifiers')->willReturn(['shipping']);
        $this->sourcePool->method('getFaqs')->willReturn([]);
        $this->assertSame('', $this->block->getJsonLd());
    }

    public function testBuildsFaqPageNode(): void
    {
        $this->config->method('isStructuredDataEnabled')->willReturn(true);
        $this->collector->method('getIdentifiers')->willReturn(['shipping']);
        $this->sourcePool->method('getFaqs')->willReturn([
            ['question' => 'Do you ship to the EU?', 'answer' => 'Yes.'],
        ]);

        $decoded = json_decode($this->block->getJsonLd(), true);

        $this->assertSame('FAQPage', $decoded['@type']);
        $this->assertCount(1, $decoded['mainEntity']);
        $this->assertSame('Question', $decoded['mainEntity'][0]['@type']);
        $this->assertSame('Do you ship to the EU?', $decoded['mainEntity'][0]['name']);
        $this->assertSame('Yes.', $decoded['mainEntity'][0]['acceptedAnswer']['text']);
    }

    public function testDeduplicatesQuestionsAcrossGroups(): void
    {
        $this->config->method('isStructuredDataEnabled')->willReturn(true);
        $this->collector->method('getIdentifiers')->willReturn(['a', 'b']);
        $this->sourcePool->method('getFaqs')->willReturnCallback(
            static fn (string $id): array => [['question' => 'Same Q', 'answer' => 'A']]
        );

        $decoded = json_decode($this->block->getJsonLd(), true);
        $this->assertCount(1, $decoded['mainEntity']);
    }

    public function testEscapesScriptBreakout(): void
    {
        $this->config->method('isStructuredDataEnabled')->willReturn(true);
        $this->collector->method('getIdentifiers')->willReturn(['x']);
        $this->sourcePool->method('getFaqs')->willReturn([
            ['question' => 'Hack</script>', 'answer' => 'A'],
        ]);
        $json = $this->block->getJsonLd();
        $this->assertStringNotContainsString('</script>', $json);
        $this->assertStringContainsString('<\/', $json);
    }
}
