<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Product\OfferEnricher;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use MageOS\Seo\Model\Product\OfferEnricher\ShippingDetailsEnricher;
use MageOS\Seo\Service\CurrencyService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingDetailsEnricherTest extends TestCase
{
    /**
     * @var ScopeConfigInterface&MockObject
     */
    private ScopeConfigInterface&MockObject $scopeConfig;

    /**
     * @var CurrencyService&MockObject
     */
    private CurrencyService&MockObject $currencyService;

    /**
     * @var ProductInterface&MockObject
     */
    private ProductInterface&MockObject $product;

    /**
     * @var ShippingDetailsEnricher
     */
    private ShippingDetailsEnricher $enricher;

    protected function setUp(): void
    {
        $this->scopeConfig     = $this->createMock(ScopeConfigInterface::class);
        $this->currencyService = $this->createMock(CurrencyService::class);
        $this->product         = $this->createMock(ProductInterface::class);
        $this->currencyService->method('getCurrentCurrencyCode')->willReturn('GBP');
        $this->enricher = new ShippingDetailsEnricher($this->scopeConfig, $this->currencyService);
    }

    /**
     * @param array<string, string> $values
     */
    private function stubConfig(bool $enabled, array $values): void
    {
        $this->scopeConfig->method('isSetFlag')->willReturn($enabled);
        $this->scopeConfig->method('getValue')->willReturnCallback(
            static fn (string $path) => $values[$path] ?? ''
        );
    }

    public function testSortOrderIsHundred(): void
    {
        $this->assertSame(100, $this->enricher->getSortOrder());
    }

    public function testReturnsEmptyWhenDisabled(): void
    {
        $this->stubConfig(false, []);
        $this->assertSame([], $this->enricher->enrich($this->product, 1));
    }

    public function testBuildsShippingDetailsWithRateAndDelivery(): void
    {
        $this->stubConfig(true, [
            'mageos_seo_merchant/shipping/label'               => 'Standard UK Delivery',
            'mageos_seo_merchant/shipping/destination_country' => 'GB',
            'mageos_seo_merchant/shipping/rate'                => '0',
            'mageos_seo_merchant/shipping/handling_min'        => '0',
            'mageos_seo_merchant/shipping/handling_max'        => '1',
            'mageos_seo_merchant/shipping/transit_min'         => '2',
            'mageos_seo_merchant/shipping/transit_max'         => '5',
        ]);

        $shipping = $this->enricher->enrich($this->product, 1)['shippingDetails'];

        $this->assertSame('OfferShippingDetails', $shipping['@type']);
        $this->assertSame('Standard UK Delivery', $shipping['shippingLabel']);
        $this->assertSame('0.00', $shipping['shippingRate']['value']);
        $this->assertSame('GBP', $shipping['shippingRate']['currency']);
        $this->assertSame('GB', $shipping['shippingDestination']['addressCountry']);
        $this->assertSame(2, $shipping['deliveryTime']['transitTime']['minValue']);
        $this->assertSame(5, $shipping['deliveryTime']['transitTime']['maxValue']);
        $this->assertSame('DAY', $shipping['deliveryTime']['transitTime']['unitCode']);
    }

    public function testDeliveryTimeOmittedWhenNoDaysConfigured(): void
    {
        $this->stubConfig(true, [
            'mageos_seo_merchant/shipping/rate' => '4.99',
        ]);
        $shipping = $this->enricher->enrich($this->product, 1)['shippingDetails'];
        $this->assertArrayNotHasKey('deliveryTime', $shipping);
        $this->assertSame('4.99', $shipping['shippingRate']['value']);
    }

    public function testLabelAndDestinationOmittedWhenEmpty(): void
    {
        $this->stubConfig(true, ['mageos_seo_merchant/shipping/rate' => '0']);
        $shipping = $this->enricher->enrich($this->product, 1)['shippingDetails'];
        $this->assertArrayNotHasKey('shippingLabel', $shipping);
        $this->assertArrayNotHasKey('shippingDestination', $shipping);
    }
}
