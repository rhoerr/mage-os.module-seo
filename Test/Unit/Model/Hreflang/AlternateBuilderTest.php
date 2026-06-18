<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Hreflang;

use MageOS\Seo\Model\Config;
use MageOS\Seo\Model\Hreflang\AlternateBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AlternateBuilderTest extends TestCase
{
    /**
     * @var Config&MockObject
     */
    private Config&MockObject $config;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
    }

    /**
     * @return array{hreflang: string, url: string, store_id: int}
     */
    private function link(string $hreflang, string $url, int $storeId): array
    {
        return ['hreflang' => $hreflang, 'url' => $url, 'store_id' => $storeId];
    }

    public function testSingleLocaleReturnsEmpty(): void
    {
        $builder = new AlternateBuilder($this->config);
        $this->assertSame([], $builder->build([$this->link('en-GB', 'https://uk/p', 1)]));
    }

    public function testEmptyInputReturnsEmpty(): void
    {
        $builder = new AlternateBuilder($this->config);
        $this->assertSame([], $builder->build([]));
    }

    public function testRegionLinksPreserved(): void
    {
        $builder = new AlternateBuilder($this->config);
        $result  = $builder->build([
            $this->link('en-GB', 'https://uk/p', 1),
            $this->link('en-US', 'https://us/p', 2),
        ]);
        $this->assertSame(['en-GB', 'en-US'], array_column($result, 'hreflang'));
    }

    public function testLanguageOnlyAddedForUniqueLanguage(): void
    {
        $this->config->method('isHreflangLanguageOnlyEnabled')->willReturn(true);
        $builder = new AlternateBuilder($this->config);
        $result  = $builder->build([
            $this->link('en-GB', 'https://uk/p', 1),
            $this->link('de-DE', 'https://de/p', 2),
        ]);
        $this->assertContains('en', array_column($result, 'hreflang'));
        $this->assertContains('de', array_column($result, 'hreflang'));
    }

    public function testLanguageOnlySkippedForSharedLanguage(): void
    {
        $this->config->method('isHreflangLanguageOnlyEnabled')->willReturn(true);
        $builder = new AlternateBuilder($this->config);
        $result  = $builder->build([
            $this->link('en-GB', 'https://uk/p', 1),
            $this->link('en-US', 'https://us/p', 2),
        ]);
        $this->assertNotContains('en', array_column($result, 'hreflang'));
    }

    public function testXDefaultUsesConfiguredStore(): void
    {
        $this->config->method('getHreflangXDefaultStoreId')->willReturn(2);
        $builder = new AlternateBuilder($this->config);
        $result  = $builder->build([
            $this->link('en-GB', 'https://uk/p', 1),
            $this->link('de-DE', 'https://de/p', 2),
        ]);
        $xDefault = array_values(array_filter($result, static fn ($l) => $l['hreflang'] === 'x-default'));
        $this->assertSame('https://de/p', $xDefault[0]['url']);
    }

    public function testXDefaultOmittedWhenNotConfigured(): void
    {
        $builder = new AlternateBuilder($this->config);
        $result  = $builder->build([
            $this->link('en-GB', 'https://uk/p', 1),
            $this->link('de-DE', 'https://de/p', 2),
        ]);
        $this->assertNotContains('x-default', array_column($result, 'hreflang'));
    }
}
