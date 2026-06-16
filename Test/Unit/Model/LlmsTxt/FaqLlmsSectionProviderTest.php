<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\LlmsTxt;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Seo\Model\Faq\SourcePool;
use MageOS\Seo\Model\LlmsTxt\FaqLlmsSectionProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FaqLlmsSectionProviderTest extends TestCase
{
    /**
     * @var SourcePool&MockObject
     */
    private SourcePool&MockObject $sourcePool;

    /**
     * @var FaqLlmsSectionProvider
     */
    private FaqLlmsSectionProvider $provider;

    protected function setUp(): void
    {
        $this->sourcePool = $this->createMock(SourcePool::class);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $store        = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $storeManager->method('getStore')->willReturn($store);

        $this->provider = new FaqLlmsSectionProvider($this->sourcePool, $storeManager);
    }

    public function testEmptyWhenNoGlobalFaqs(): void
    {
        $this->sourcePool->method('getFaqs')->willReturn([]);
        $this->assertSame('', $this->provider->getConciseSection());
        $this->assertSame('', $this->provider->getFullSection());
    }

    public function testFullSectionRendersAllFaqsAsMarkdown(): void
    {
        $this->sourcePool->method('getFaqs')->with('global', 1)->willReturn([
            ['question' => 'Q1', 'answer' => 'A1'],
            ['question' => 'Q2', 'answer' => 'A2'],
        ]);

        $section = $this->provider->getFullSection();

        $this->assertStringContainsString('## Frequently Asked Questions', $section);
        $this->assertStringContainsString('**Q1**', $section);
        $this->assertStringContainsString('A1', $section);
        $this->assertStringContainsString('**Q2**', $section);
    }

    public function testConciseSectionLimitsToFiveEntries(): void
    {
        $faqs = [];
        for ($i = 1; $i <= 8; $i++) {
            $faqs[] = ['question' => "Q$i", 'answer' => "A$i"];
        }
        $this->sourcePool->method('getFaqs')->willReturn($faqs);

        $section = $this->provider->getConciseSection();

        $this->assertStringContainsString('**Q5**', $section);
        $this->assertStringNotContainsString('**Q6**', $section);
    }
}
