<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Ucp;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Seo\Model\Ucp\UcpConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UcpConfigTest extends TestCase
{
    private ScopeConfigInterface&MockObject $scopeConfig;
    private StoreManagerInterface&MockObject $storeManager;
    private UcpConfig $config;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->config = new UcpConfig($this->scopeConfig, $this->storeManager);

        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')->willReturn('https://shop.example.co.uk/');
        $this->storeManager->method('getStore')->willReturn($store);
    }

    /**
     * @param array<string, string> $values
     */
    private function withValues(array $values): void
    {
        $this->scopeConfig->method('getValue')->willReturnCallback(
            static fn (string $path): ?string => $values[$path] ?? null
        );
    }

    /**
     * @param array<string, bool> $flags
     */
    private function withFlags(array $flags): void
    {
        $this->scopeConfig->method('isSetFlag')->willReturnCallback(
            static fn (string $path): bool => $flags[$path] ?? false
        );
    }

    public function testBaseUrlAndDomainHost(): void
    {
        $this->withValues([]);
        $this->assertSame('https://shop.example.co.uk', $this->config->getBaseUrl());
        $this->assertSame('shop.example.co.uk', $this->config->getDomainHost());
    }

    public function testMerchantIdDerivedFromDomainWhenBlank(): void
    {
        $this->withValues([UcpConfig::XML_UCP_MERCHANT_ID => '']);
        $this->assertSame('uk.co.example.shop', $this->config->getMerchantId());
    }

    public function testConfiguredMerchantIdWins(): void
    {
        $this->withValues([UcpConfig::XML_UCP_MERCHANT_ID => 'com.brand']);
        $this->assertSame('com.brand', $this->config->getMerchantId());
    }

    public function testMerchantNameFallsBackToStoreInformation(): void
    {
        $this->withValues([
            UcpConfig::XML_UCP_MERCHANT_NAME => '',
            'general/store_information/name' => 'Fallback Store',
        ]);
        $this->assertSame('Fallback Store', $this->config->getMerchantName());
    }

    public function testEnabledCapabilitiesReflectFlags(): void
    {
        $this->withValues([]);
        $this->withFlags([
            'mageos_seo_ucp/capabilities/catalog'  => true,
            'mageos_seo_ucp/capabilities/checkout' => true,
        ]);

        $this->assertSame(['catalog', 'checkout'], $this->config->getEnabledCapabilities());
    }

    public function testEnabledFlagsDelegateToIsSetFlag(): void
    {
        $this->withValues([]);
        $this->withFlags([
            UcpConfig::XML_UCP_ENABLED          => true,
            UcpConfig::XML_AI_PLUGIN_ENABLED    => false,
            UcpConfig::XML_SECURITY_TXT_ENABLED => true,
        ]);

        $this->assertTrue($this->config->isUcpEnabled());
        $this->assertFalse($this->config->isAiPluginEnabled());
        $this->assertTrue($this->config->isSecurityTxtEnabled());
    }
}
