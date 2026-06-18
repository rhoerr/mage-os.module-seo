<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Hreflang;

use MageOS\Seo\Model\Hreflang\LinkBuilder;
use MageOS\Seo\Model\Hreflang\StoreLocaleMap;
use MageOS\Seo\Model\Hreflang\UrlRewriteFetcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LinkBuilderTest extends TestCase
{
    /**
     * @var UrlRewriteFetcher&MockObject
     */
    private UrlRewriteFetcher&MockObject $fetcher;

    /**
     * @var StoreLocaleMap&MockObject
     */
    private StoreLocaleMap&MockObject $storeLocaleMap;

    /**
     * @var LinkBuilder
     */
    private LinkBuilder $linkBuilder;

    protected function setUp(): void
    {
        $this->fetcher        = $this->createMock(UrlRewriteFetcher::class);
        $this->storeLocaleMap = $this->createMock(StoreLocaleMap::class);
        $this->linkBuilder    = new LinkBuilder($this->fetcher, $this->storeLocaleMap);
    }

    public function testBuildsAbsoluteLinksFromRewritesAndMap(): void
    {
        $this->fetcher->method('fetchForEntity')->with('product', 5)
            ->willReturn([1 => 'blue-shirt.html', 2 => 'us/blue-shirt.html']);
        $this->storeLocaleMap->method('getMap')->willReturn([
            1 => ['base_url' => 'https://uk', 'locale' => 'en-GB', 'language' => 'en'],
            2 => ['base_url' => 'https://us', 'locale' => 'en-US', 'language' => 'en'],
        ]);

        $links = $this->linkBuilder->build('product', 5);

        $this->assertSame('en-GB', $links[0]['hreflang']);
        $this->assertSame('https://uk/blue-shirt.html', $links[0]['url']);
        $this->assertSame(1, $links[0]['store_id']);
        $this->assertSame('https://us/us/blue-shirt.html', $links[1]['url']);
    }

    public function testStoresMissingFromMapAreSkipped(): void
    {
        $this->fetcher->method('fetchForEntity')->willReturn([1 => 'p.html', 3 => 'excluded.html']);
        $this->storeLocaleMap->method('getMap')->willReturn([
            1 => ['base_url' => 'https://uk', 'locale' => 'en-GB', 'language' => 'en'],
        ]);

        $links = $this->linkBuilder->build('product', 5);

        $this->assertCount(1, $links);
        $this->assertSame(1, $links[0]['store_id']);
    }

    public function testReturnsEmptyWhenNoRewrites(): void
    {
        $this->fetcher->method('fetchForEntity')->willReturn([]);
        $this->storeLocaleMap->method('getMap')->willReturn([
            1 => ['base_url' => 'https://uk', 'locale' => 'en-GB', 'language' => 'en'],
        ]);
        $this->assertSame([], $this->linkBuilder->build('product', 5));
    }
}
