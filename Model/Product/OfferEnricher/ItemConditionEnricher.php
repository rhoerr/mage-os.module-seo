<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Product\OfferEnricher;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use MageOS\Seo\Api\OfferEnricherInterface;

/**
 * Sets the Offer itemCondition from the configured store default.
 */
class ItemConditionEnricher implements OfferEnricherInterface
{
    private const XML_DEFAULT_CONDITION = 'mageos_seo_merchant/condition/default_condition';

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
        $condition = (string) $this->scopeConfig->getValue(
            self::XML_DEFAULT_CONDITION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($condition === '') {
            return [];
        }

        return ['itemCondition' => $condition];
    }

    /**
     * @inheritdoc
     */
    public function getSortOrder(): int
    {
        return 100;
    }
}
