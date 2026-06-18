<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Ucp;

use MageOS\Seo\Api\UcpCapabilityProviderInterface;
use MageOS\Seo\Model\Ucp\CapabilityPool;
use PHPUnit\Framework\TestCase;

class CapabilityPoolTest extends TestCase
{
    /**
     * @param array<string, mixed> $data
     */
    private function provider(string $key, bool $enabled, array $data): UcpCapabilityProviderInterface
    {
        $provider = $this->createMock(UcpCapabilityProviderInterface::class);
        $provider->method('getCapabilityKey')->willReturn($key);
        $provider->method('isEnabled')->willReturn($enabled);
        $provider->method('getCapabilityData')->willReturn($data);

        return $provider;
    }

    public function testEmptyPoolReturnsNoCapabilities(): void
    {
        $this->assertSame([], (new CapabilityPool())->getEnabledCapabilities());
    }

    public function testOnlyEnabledProvidersAreIncludedKeyedByCapabilityKey(): void
    {
        $pool = new CapabilityPool([
            $this->provider('dev.ucp.shopping.catalog', true, ['enabled' => true]),
            $this->provider('dev.ucp.shopping.cart', false, ['enabled' => true]),
        ]);

        $result = $pool->getEnabledCapabilities();

        $this->assertArrayHasKey('dev.ucp.shopping.catalog', $result);
        $this->assertArrayNotHasKey('dev.ucp.shopping.cart', $result);
        $this->assertSame(['enabled' => true], $result['dev.ucp.shopping.catalog']);
    }
}
