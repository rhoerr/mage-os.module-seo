<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Hreflang;

use MageOS\Seo\Model\Hreflang\AlternateBuilder;
use MageOS\Seo\Model\Hreflang\LinkBuilder;
use MageOS\Seo\Model\Hreflang\SitemapGenerator;
use MageOS\Seo\Model\Hreflang\StoreLocaleMap;
use MageOS\Seo\Model\Hreflang\UrlRewriteFetcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SitemapGeneratorTest extends TestCase
{
    /**
     * @var StoreLocaleMap&MockObject
     */
    private StoreLocaleMap&MockObject $storeLocaleMap;

    /**
     * @var UrlRewriteFetcher&MockObject
     */
    private UrlRewriteFetcher&MockObject $urlRewriteFetcher;

    /**
     * @var LinkBuilder&MockObject
     */
    private LinkBuilder&MockObject $linkBuilder;

    /**
     * @var AlternateBuilder&MockObject
     */
    private AlternateBuilder&MockObject $alternateBuilder;

    /**
     * @var SitemapGenerator
     */
    private SitemapGenerator $generator;

    protected function setUp(): void
    {
        $this->storeLocaleMap    = $this->createMock(StoreLocaleMap::class);
        $this->urlRewriteFetcher = $this->createMock(UrlRewriteFetcher::class);
        $this->linkBuilder       = $this->createMock(LinkBuilder::class);
        $this->alternateBuilder  = $this->createMock(AlternateBuilder::class);
        $this->generator         = new SitemapGenerator(
            $this->storeLocaleMap,
            $this->urlRewriteFetcher,
            $this->linkBuilder,
            $this->alternateBuilder
        );

        $this->storeLocaleMap->method('getMap')->willReturn([
            1 => ['base_url' => 'https://uk', 'locale' => 'en-GB', 'language' => 'en'],
            2 => ['base_url' => 'https://de', 'locale' => 'de-DE', 'language' => 'de'],
        ]);

        // Region links → alternate set ([] when fewer than 2 distinct locales).
        $this->alternateBuilder->method('build')->willReturnCallback(
            static function (array $regionLinks): array {
                if (\count($regionLinks) < 2) {
                    return [];
                }
                return array_map(
                    static fn (array $l) => ['hreflang' => $l['hreflang'], 'url' => $l['url']],
                    $regionLinks
                );
            }
        );
    }

    private function noEntities(): void
    {
        $this->urlRewriteFetcher->method('fetchAllForType')->willReturn([]);
    }

    public function testProducesValidUrlsetDocument(): void
    {
        $this->noEntities();
        $xml = $this->generator->generate();
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('xmlns:xhtml="http://www.w3.org/1999/xhtml"', $xml);
        $this->assertStringContainsString('</urlset>', $xml);
    }

    public function testHomePagesEmittedPerStore(): void
    {
        $this->noEntities();
        $xml = $this->generator->generate();
        $this->assertStringContainsString('<loc>https://uk/</loc>', $xml);
        $this->assertStringContainsString('<loc>https://de/</loc>', $xml);
        // Each url block carries both alternates.
        $this->assertStringContainsString('hreflang="en-GB" href="https://uk/"', $xml);
        $this->assertStringContainsString('hreflang="de-DE" href="https://de/"', $xml);
    }

    public function testEntityEmitsOneUrlBlockPerStoreWithAlternates(): void
    {
        $this->urlRewriteFetcher->method('fetchAllForType')->willReturnCallback(
            static fn (string $type) => $type === 'product' ? [5 => [1 => 'p.html', 2 => 'p-de.html']] : []
        );
        $this->linkBuilder->method('buildFromPaths')->willReturn([
            ['hreflang' => 'en-GB', 'url' => 'https://uk/p.html', 'store_id' => 1],
            ['hreflang' => 'de-DE', 'url' => 'https://de/p-de.html', 'store_id' => 2],
        ]);

        $xml = $this->generator->generate();

        $ukBlocks = substr_count($xml, '<loc>https://uk/p.html</loc>');
        $deBlocks = substr_count($xml, '<loc>https://de/p-de.html</loc>');
        $this->assertSame(1, $ukBlocks);
        $this->assertSame(1, $deBlocks);
    }

    public function testSingleStoreEntityProducesNoBlocks(): void
    {
        $this->urlRewriteFetcher->method('fetchAllForType')->willReturnCallback(
            static fn (string $type) => $type === 'product' ? [5 => [1 => 'p.html']] : []
        );
        $this->linkBuilder->method('buildFromPaths')->willReturn([
            ['hreflang' => 'en-GB', 'url' => 'https://uk/p.html', 'store_id' => 1],
        ]);

        $this->assertStringNotContainsString('p.html', $this->generator->generate());
    }

    public function testXmlSpecialCharactersAreEscaped(): void
    {
        $this->urlRewriteFetcher->method('fetchAllForType')->willReturnCallback(
            static fn (string $type) => $type === 'product' ? [5 => [1 => 'a', 2 => 'b']] : []
        );
        $this->linkBuilder->method('buildFromPaths')->willReturn([
            ['hreflang' => 'en-GB', 'url' => 'https://uk/p?a=1&b=2', 'store_id' => 1],
            ['hreflang' => 'de-DE', 'url' => 'https://de/p', 'store_id' => 2],
        ]);

        $xml = $this->generator->generate();
        $this->assertStringContainsString('a=1&amp;b=2', $xml);
        $this->assertStringNotContainsString('a=1&b=2', $xml);
    }
}
