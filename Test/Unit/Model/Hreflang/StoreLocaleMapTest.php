<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Hreflang;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Seo\Model\Config;
use MageOS\Seo\Model\Hreflang\StoreLocaleMap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StoreLocaleMapTest extends TestCase
{
    /**
     * @var StoreManagerInterface&MockObject
     */
    private StoreManagerInterface&MockObject $storeManager;

    /**
     * @var ScopeConfigInterface&MockObject
     */
    private ScopeConfigInterface&MockObject $scopeConfig;

    /**
     * @var Config&MockObject
     */
    private Config&MockObject $config;

    protected function setUp(): void
    {
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->scopeConfig  = $this->createMock(ScopeConfigInterface::class);
        $this->config       = $this->createMock(Config::class);
    }

    private function makeStore(int $id, bool $active, string $baseUrl): Store&MockObject
    {
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn($id);
        $store->method('getIsActive')->willReturn($active);
        $store->method('getBaseUrl')->willReturn($baseUrl);
        return $store;
    }

    /**
     * @param array<int, Store&MockObject> $stores
     * @param array<int, string> $locales
     * @param int[] $excluded
     */
    private function map(array $stores, array $locales, array $excluded = []): StoreLocaleMap
    {
        $this->storeManager->method('getStores')->willReturn($stores);
        $this->config->method('getHreflangExcludedStoreIds')->willReturn($excluded);
        $this->scopeConfig->method('getValue')->willReturnCallback(
            static fn (string $path, string $scope, $scopeId) => $locales[(int) $scopeId] ?? ''
        );
        return new StoreLocaleMap($this->storeManager, $this->scopeConfig, $this->config);
    }

    public function testFormatLocaleConvertsToBcp47(): void
    {
        $map = new StoreLocaleMap($this->storeManager, $this->scopeConfig, $this->config);
        $this->assertSame('en-GB', $map->formatLocale('en_GB'));
    }

    public function testExtractLanguageReturnsBaseLanguage(): void
    {
        $map = new StoreLocaleMap($this->storeManager, $this->scopeConfig, $this->config);
        $this->assertSame('en', $map->extractLanguage('en-GB'));
    }

    public function testBuildsMapForActiveStores(): void
    {
        $map = $this->map(
            [$this->makeStore(1, true, 'https://uk/'), $this->makeStore(2, true, 'https://de/')],
            [1 => 'en_GB', 2 => 'de_DE']
        );
        $result = $map->getMap();
        $this->assertSame('https://uk', $result[1]['base_url']);
        $this->assertSame('en-GB', $result[1]['locale']);
        $this->assertSame('en', $result[1]['language']);
        $this->assertSame('de-DE', $result[2]['locale']);
    }

    public function testInactiveStoresAreExcluded(): void
    {
        $map = $this->map(
            [$this->makeStore(1, true, 'https://uk/'), $this->makeStore(2, false, 'https://de/')],
            [1 => 'en_GB', 2 => 'de_DE']
        );
        $this->assertArrayNotHasKey(2, $map->getMap());
    }

    public function testConfiguredExcludedStoresAreOmitted(): void
    {
        $map = $this->map(
            [$this->makeStore(1, true, 'https://uk/'), $this->makeStore(2, true, 'https://de/')],
            [1 => 'en_GB', 2 => 'de_DE'],
            [2]
        );
        $this->assertArrayNotHasKey(2, $map->getMap());
        $this->assertArrayHasKey(1, $map->getMap());
    }

    public function testStoresWithoutLocaleAreSkipped(): void
    {
        $map = $this->map(
            [$this->makeStore(1, true, 'https://uk/'), $this->makeStore(2, true, 'https://de/')],
            [1 => 'en_GB', 2 => '']
        );
        $this->assertArrayNotHasKey(2, $map->getMap());
    }

    public function testMapIsMemoised(): void
    {
        $this->storeManager->expects($this->once())->method('getStores')
            ->willReturn([$this->makeStore(1, true, 'https://uk/')]);
        $this->config->method('getHreflangExcludedStoreIds')->willReturn([]);
        $this->scopeConfig->method('getValue')->willReturn('en_GB');
        $map = new StoreLocaleMap($this->storeManager, $this->scopeConfig, $this->config);
        $map->getMap();
        $map->getMap();
    }
}
