<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Ucp;

use Magento\Framework\Exception\LocalizedException;
use MageOS\Seo\Model\Ucp\CapabilityPool;
use MageOS\Seo\Model\Ucp\ProfileBuilder;
use MageOS\Seo\Model\Ucp\UcpConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProfileBuilderTest extends TestCase
{
    private UcpConfig&MockObject $config;
    private CapabilityPool&MockObject $pool;
    private ProfileBuilder $builder;

    protected function setUp(): void
    {
        $this->config = $this->createMock(UcpConfig::class);
        $this->pool = $this->createMock(CapabilityPool::class);
        $this->builder = new ProfileBuilder($this->config, $this->pool);

        $this->config->method('getBaseUrl')->willReturn('https://shop.test');
        $this->config->method('getMerchantId')->willReturn('test.shop');
        $this->config->method('getMerchantName')->willReturn('Shop');
        $this->pool->method('getEnabledCapabilities')->willReturn([]);
    }

    public function testMinimalManifestHasMerchantTransportAndShoppingCapability(): void
    {
        $this->config->method('getEnabledCapabilities')->willReturn([]);
        $this->config->method('getPublicKeyJwk')->willReturn('');

        $manifest = $this->builder->build();

        $this->assertSame('2026-04-08', $manifest['version']);
        $this->assertSame('test.shop', $manifest['merchant']['id']);
        $this->assertSame('Shop', $manifest['merchant']['name']);
        $this->assertSame('https://shop.test', $manifest['merchant']['domain']);
        $this->assertSame('https://shop.test', $manifest['transports']['rest']['baseUrl']);
        $this->assertTrue($manifest['capabilities']['dev.ucp.shopping']['enabled']);
        $this->assertArrayNotHasKey('signing_keys', $manifest);
    }

    public function testEnabledCapabilitiesAreNestedUnderShopping(): void
    {
        $this->config->method('getEnabledCapabilities')->willReturn(['catalog', 'cart']);
        $this->config->method('getPublicKeyJwk')->willReturn('');

        $shopping = $this->builder->build()['capabilities']['dev.ucp.shopping'];

        $this->assertTrue($shopping['catalog']['enabled']);
        $this->assertTrue($shopping['cart']['enabled']);
        $this->assertArrayNotHasKey('checkout', $shopping);
    }

    public function testCapabilityPoolEntriesAreMerged(): void
    {
        $this->config->method('getEnabledCapabilities')->willReturn([]);
        $this->config->method('getPublicKeyJwk')->willReturn('');
        $this->pool = $this->createMock(CapabilityPool::class);
        $this->pool->method('getEnabledCapabilities')->willReturn([
            'dev.ucp.shopping.catalog' => ['enabled' => true, 'endpoint' => '/rest/V1/products'],
        ]);
        $builder = new ProfileBuilder($this->config, $this->pool);

        $capabilities = $builder->build()['capabilities'];

        $this->assertArrayHasKey('dev.ucp.shopping.catalog', $capabilities);
        $this->assertSame('/rest/V1/products', $capabilities['dev.ucp.shopping.catalog']['endpoint']);
    }

    public function testValidPublicJwkIsIncluded(): void
    {
        $this->config->method('getEnabledCapabilities')->willReturn([]);
        $this->config->method('getPublicKeyJwk')->willReturn(
            '{"kty":"EC","crv":"P-256","kid":"ucp-key-2026-06","x":"AAA","y":"BBB"}'
        );

        $manifest = $this->builder->build();

        $this->assertCount(1, $manifest['signing_keys']);
        $this->assertSame('ucp-key-2026-06', $manifest['signing_keys'][0]['kid']);
        $this->assertArrayNotHasKey('d', $manifest['signing_keys'][0]);
    }

    public function testLeakedPrivateKeyJwkThrows(): void
    {
        $this->config->method('getEnabledCapabilities')->willReturn([]);
        $this->config->method('getPublicKeyJwk')->willReturn(
            '{"kty":"EC","crv":"P-256","x":"AAA","y":"BBB","d":"SECRET"}'
        );

        $this->expectException(LocalizedException::class);
        $this->builder->build();
    }

    public function testInvalidJwkJsonIsIgnored(): void
    {
        $this->config->method('getEnabledCapabilities')->willReturn([]);
        $this->config->method('getPublicKeyJwk')->willReturn('not json');

        $this->assertArrayNotHasKey('signing_keys', $this->builder->build());
    }
}
