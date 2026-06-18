<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Product\OfferEnricher;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use MageOS\Seo\Api\OfferEnricherInterface;

/**
 * Adds a hasMerchantReturnPolicy node to the Offer from store configuration.
 *
 * Required for Google Merchant free-listing eligibility and the "30-day returns" badge. Emits
 * nothing unless explicitly enabled, and omits individual fields that are not configured so a
 * partially-configured store still produces valid schema.
 */
class ReturnPolicyEnricher implements OfferEnricherInterface
{
    private const XML_ENABLED         = 'mageos_seo_merchant/return/enabled';
    private const XML_COUNTRY         = 'mageos_seo_merchant/return/applicable_country';
    private const XML_POLICY_CATEGORY = 'mageos_seo_merchant/return/policy_category';
    private const XML_DAYS            = 'mageos_seo_merchant/return/days';
    private const XML_METHOD          = 'mageos_seo_merchant/return/method';
    private const XML_FEES            = 'mageos_seo_merchant/return/fees';
    private const XML_REFUND_TYPE     = 'mageos_seo_merchant/return/refund_type';
    private const XML_POLICY_URL      = 'mageos_seo_merchant/return/policy_url';

    private const FINITE_WINDOW = 'https://schema.org/MerchantReturnFiniteReturnWindow';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @inheritdoc
     */
    public function enrich(ProductInterface $product, int $storeId): array
    {
        if (!$this->scopeConfig->isSetFlag(self::XML_ENABLED, ScopeInterface::SCOPE_STORE, $storeId)) {
            return [];
        }

        $policy = ['@type' => 'MerchantReturnPolicy'];

        $country = $this->value(self::XML_COUNTRY, $storeId);
        if ($country !== '') {
            $policy['applicableCountry'] = $country;
        }

        $category = $this->value(self::XML_POLICY_CATEGORY, $storeId);
        if ($category !== '') {
            $policy['returnPolicyCategory'] = $category;
            // merchantReturnDays only applies to a finite return window.
            if ($category === self::FINITE_WINDOW) {
                $days = (int) $this->value(self::XML_DAYS, $storeId);
                if ($days > 0) {
                    $policy['merchantReturnDays'] = $days;
                }
            }
        }

        $optionalFields = [
            'returnMethod' => self::XML_METHOD,
            'returnFees'   => self::XML_FEES,
            'refundType'   => self::XML_REFUND_TYPE,
        ];
        foreach ($optionalFields as $key => $path) {
            $value = $this->value($path, $storeId);
            if ($value !== '') {
                $policy[$key] = $value;
            }
        }

        $url = $this->value(self::XML_POLICY_URL, $storeId);
        if ($url !== '') {
            $policy['merchantReturnLink'] = $url;
        }

        return ['hasMerchantReturnPolicy' => $policy];
    }

    /**
     * @inheritdoc
     */
    public function getSortOrder(): int
    {
        return 100;
    }

    /**
     * Read a store-scoped config value as a trimmed string.
     *
     * @param string $path
     * @param int $storeId
     * @return string
     */
    private function value(string $path, int $storeId): string
    {
        return trim((string) $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId));
    }
}
