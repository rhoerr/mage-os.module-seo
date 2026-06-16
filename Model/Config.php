<?php

declare(strict_types=1);

namespace MageOS\Seo\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public const XML_OG_TAGS_ENABLED               = 'mageos_seo_general/og_tags/enabled';
    public const XML_SD_ENABLED                    = 'mageos_seo_general/structured_data/enabled';
    public const XML_SD_DEFAULT_TEMPLATE           = 'mageos_seo_general/structured_data/default_product_template';
    public const XML_SD_CATEGORY_ITEM_LIST_ENABLED = 'mageos_seo_general/structured_data/category_item_list_enabled';
    public const XML_SD_CATEGORY_ITEM_LIST_MAX     = 'mageos_seo_general/structured_data/category_item_list_max';
    public const XML_SD_HAS_VARIANT_MAX            = 'mageos_seo_general/structured_data/has_variant_max';
    public const XML_SD_PRICE_VALID_UNTIL_MONTHS   = 'mageos_seo_general/structured_data/price_valid_until_months';
    public const XML_SD_AGGREGATE_RATING_ENABLED   = 'mageos_seo_general/structured_data/aggregate_rating_enabled';
    public const XML_LLMS_ENABLED                  = 'mageos_seo_general/llms_txt/enabled';
    public const XML_LLMS_FULL_ENABLED             = 'mageos_seo_general/llms_txt/full_enabled';
    public const XML_ROBOTS_PRODUCT_DEFAULT        = 'mageos_seo_general/robots_meta/product_default';
    public const XML_ROBOTS_CATEGORY_DEFAULT       = 'mageos_seo_general/robots_meta/category_default';
    public const XML_ROBOTS_CMS_DEFAULT            = 'mageos_seo_general/robots_meta/cms_page_default';
    public const XML_ROBOTS_PAGINATED_ENABLED      = 'mageos_seo_general/robots_meta/paginated_enabled';
    public const XML_ROBOTS_PAGINATED              = 'mageos_seo_general/robots_meta/paginated_robots';
    public const XML_HREFLANG_ENABLED              = 'mageos_seo_general/hreflang/enabled';
    public const XML_HREFLANG_XDEFAULT_STORE       = 'mageos_seo_general/hreflang/xdefault_store_id';
    public const XML_HREFLANG_EXCLUDED_STORES      = 'mageos_seo_general/hreflang/excluded_store_ids';
    public const XML_HREFLANG_LANGUAGE_ONLY        = 'mageos_seo_general/hreflang/language_only_enabled';
    public const XML_HREFLANG_SITEMAP_ENABLED      = 'mageos_seo_general/hreflang/sitemap_enabled';

    /**
     * Initialize Config with scope configuration.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Check if Open Graph tags output is enabled.
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isOgTagsEnabled(int|string|null $storeId = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_OG_TAGS_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if structured data output is enabled.
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isStructuredDataEnabled(int|string|null $storeId = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_SD_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Return the default product schema template code.
     *
     * @param int|string|null $storeId
     * @return string
     */
    public function getDefaultProductTemplate(int|string|null $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_SD_DEFAULT_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if the category ItemList schema block is enabled.
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isCategoryItemListEnabled(int|string|null $storeId = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_SD_CATEGORY_ITEM_LIST_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Return the maximum number of products to include in a category ItemList.
     *
     * @param int|string|null $storeId
     * @return int
     */
    public function getCategoryItemListMax(int|string|null $storeId = null): int
    {
        return max(1, (int) ($this->scopeConfig->getValue(
            self::XML_SD_CATEGORY_ITEM_LIST_MAX,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 36));
    }

    /**
     * Return the maximum number of hasVariant offers to render per product.
     *
     * @param int|string|null $storeId
     * @return int
     */
    public function getHasVariantMax(int|string|null $storeId = null): int
    {
        return max(1, (int) ($this->scopeConfig->getValue(
            self::XML_SD_HAS_VARIANT_MAX,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 50));
    }

    /**
     * Return the number of months used to calculate priceValidUntil.
     *
     * @param int|string|null $storeId
     * @return int
     */
    public function getPriceValidUntilMonths(int|string|null $storeId = null): int
    {
        return max(1, (int) $this->scopeConfig->getValue(
            self::XML_SD_PRICE_VALID_UNTIL_MONTHS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * Check if AggregateRating output on product schema is enabled.
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isAggregateRatingEnabled(int|string|null $storeId = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_SD_AGGREGATE_RATING_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if the llms.txt endpoint is enabled.
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isLlmsTxtEnabled(int|string|null $storeId = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_LLMS_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if the llms-full.txt endpoint is enabled.
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isLlmsFullTxtEnabled(int|string|null $storeId = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_LLMS_FULL_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Return the default robots meta value for product pages.
     *
     * @param int|string|null $storeId
     * @return string
     */
    public function getRobotsProductDefault(int|string|null $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_ROBOTS_PRODUCT_DEFAULT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Return the default robots meta value for category pages.
     *
     * @param int|string|null $storeId
     * @return string
     */
    public function getRobotsCategoryDefault(int|string|null $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_ROBOTS_CATEGORY_DEFAULT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Return the default robots meta value for CMS pages.
     *
     * @param int|string|null $storeId
     * @return string
     */
    public function getRobotsCmsDefault(int|string|null $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_ROBOTS_CMS_DEFAULT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check whether a dedicated robots meta is applied to paginated listing pages (?p=N, N>1).
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isPaginatedRobotsEnabled(int|string|null $storeId = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_ROBOTS_PAGINATED_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Return the robots meta value applied to paginated listing pages (?p=N, N>1).
     *
     * @param int|string|null $storeId
     * @return string
     */
    public function getRobotsPaginated(int|string|null $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_ROBOTS_PAGINATED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if hreflang alternate output is enabled.
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isHreflangEnabled(int|string|null $storeId = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_HREFLANG_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Return the store view ID to advertise as hreflang x-default (0 = none).
     *
     * @return int
     */
    public function getHreflangXDefaultStoreId(): int
    {
        return (int) $this->scopeConfig->getValue(self::XML_HREFLANG_XDEFAULT_STORE);
    }

    /**
     * Return store view IDs excluded from all hreflang output.
     *
     * @return int[]
     */
    public function getHreflangExcludedStoreIds(): array
    {
        $raw = (string) $this->scopeConfig->getValue(self::XML_HREFLANG_EXCLUDED_STORES);
        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $id): int => (int) trim($id),
            explode(',', $raw)
        )));
    }

    /**
     * Check if automatic language-only hreflang tags are enabled.
     *
     * @return bool
     */
    public function isHreflangLanguageOnlyEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::XML_HREFLANG_LANGUAGE_ONLY);
    }

    /**
     * Check if the /hreflang-sitemap.xml endpoint is enabled.
     *
     * @return bool
     */
    public function isHreflangSitemapEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::XML_HREFLANG_SITEMAP_ENABLED);
    }
}
