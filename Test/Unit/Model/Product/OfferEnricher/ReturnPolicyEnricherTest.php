<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Product\OfferEnricher;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use MageOS\Seo\Model\Product\OfferEnricher\ReturnPolicyEnricher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReturnPolicyEnricherTest extends TestCase
{
    /**
     * @var ScopeConfigInterface&MockObject
     */
    private ScopeConfigInterface&MockObject $scopeConfig;

    /**
     * @var ProductInterface&MockObject
     */
    private ProductInterface&MockObject $product;

    /**
     * @var ReturnPolicyEnricher
     */
    private ReturnPolicyEnricher $enricher;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->product     = $this->createMock(ProductInterface::class);
        $this->enricher    = new ReturnPolicyEnricher($this->scopeConfig);
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

    public function testBuildsFullPolicy(): void
    {
        $this->stubConfig(true, [
            'mageos_seo_merchant/return/applicable_country' => 'GB',
            'mageos_seo_merchant/return/policy_category'    => 'https://schema.org/MerchantReturnFiniteReturnWindow',
            'mageos_seo_merchant/return/days'               => '30',
            'mageos_seo_merchant/return/method'             => 'https://schema.org/ReturnByMail',
            'mageos_seo_merchant/return/fees'               => 'https://schema.org/FreeReturn',
            'mageos_seo_merchant/return/refund_type'        => 'https://schema.org/FullRefund',
            'mageos_seo_merchant/return/policy_url'         => 'https://example.com/returns',
        ]);

        $policy = $this->enricher->enrich($this->product, 1)['hasMerchantReturnPolicy'];

        $this->assertSame('MerchantReturnPolicy', $policy['@type']);
        $this->assertSame('GB', $policy['applicableCountry']);
        $this->assertSame(30, $policy['merchantReturnDays']);
        $this->assertSame('https://schema.org/ReturnByMail', $policy['returnMethod']);
        $this->assertSame('https://schema.org/FreeReturn', $policy['returnFees']);
        $this->assertSame('https://schema.org/FullRefund', $policy['refundType']);
        $this->assertSame('https://example.com/returns', $policy['merchantReturnLink']);
    }

    public function testReturnDaysOmittedWhenZero(): void
    {
        $this->stubConfig(true, [
            'mageos_seo_merchant/return/policy_category' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
            'mageos_seo_merchant/return/days'            => '0',
        ]);
        $policy = $this->enricher->enrich($this->product, 1)['hasMerchantReturnPolicy'];
        $this->assertArrayNotHasKey('merchantReturnDays', $policy);
    }

    public function testReturnDaysOmittedWhenNotFiniteWindow(): void
    {
        $this->stubConfig(true, [
            'mageos_seo_merchant/return/policy_category' => 'https://schema.org/MerchantReturnUnlimitedWindow',
            'mageos_seo_merchant/return/days'            => '30',
        ]);
        $policy = $this->enricher->enrich($this->product, 1)['hasMerchantReturnPolicy'];
        $this->assertArrayNotHasKey('merchantReturnDays', $policy);
    }

    public function testMinimalPolicyWhenOnlyEnabled(): void
    {
        $this->stubConfig(true, []);
        $policy = $this->enricher->enrich($this->product, 1)['hasMerchantReturnPolicy'];
        $this->assertSame(['@type' => 'MerchantReturnPolicy'], $policy);
    }
}
