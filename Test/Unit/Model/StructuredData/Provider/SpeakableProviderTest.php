<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\StructuredData\Provider;

use MageOS\Seo\Model\Config;
use MageOS\Seo\Model\StructuredData\Provider\SpeakableProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SpeakableProviderTest extends TestCase
{
    /**
     * @var Config&MockObject
     */
    private Config&MockObject $config;

    /**
     * @var SpeakableProvider
     */
    private SpeakableProvider $provider;

    protected function setUp(): void
    {
        $this->config   = $this->createMock(Config::class);
        $this->provider = new SpeakableProvider($this->config);
    }

    public function testHandlesEveryPage(): void
    {
        $this->assertSame(['*'], $this->provider->getHandles());
    }

    public function testReturnsNothingWhenDisabled(): void
    {
        $this->config->method('isSpeakableEnabled')->willReturn(false);
        $this->assertSame([], $this->provider->getSchemas());
    }

    public function testReturnsNothingWhenNoSelectors(): void
    {
        $this->config->method('isSpeakableEnabled')->willReturn(true);
        $this->config->method('getSpeakableCssSelectors')->willReturn([]);
        $this->assertSame([], $this->provider->getSchemas());
    }

    public function testEmitsSpeakableSpecificationWhenEnabled(): void
    {
        $this->config->method('isSpeakableEnabled')->willReturn(true);
        $this->config->method('getSpeakableCssSelectors')->willReturn(['.page-title', '.description']);

        $schemas = $this->provider->getSchemas();

        $this->assertCount(1, $schemas);
        $this->assertSame('WebPage', $schemas[0]['@type']);
        $this->assertSame('SpeakableSpecification', $schemas[0]['speakable']['@type']);
        $this->assertSame(['.page-title', '.description'], $schemas[0]['speakable']['cssSelector']);
    }
}
