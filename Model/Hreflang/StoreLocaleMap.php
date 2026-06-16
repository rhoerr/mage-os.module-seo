<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Hreflang;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Seo\Model\Config;

/**
 * Request-scoped map of active store views to their base URL and BCP 47 locale.
 *
 * Excluded and inactive stores are filtered here so downstream resolvers and the sitemap never see
 * them. Built once per request and memoised.
 */
class StoreLocaleMap
{
    /**
     * @var array<int, array{base_url: string, locale: string, language: string}>|null
     */
    private ?array $map = null;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $seoConfig
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly ScopeConfigInterface  $scopeConfig,
        private readonly Config                $seoConfig
    ) {
    }

    /**
     * Return store_id => [base_url, locale, language] for all eligible store views.
     *
     * @return array<int, array{base_url: string, locale: string, language: string}>
     */
    public function getMap(): array
    {
        if ($this->map !== null) {
            return $this->map;
        }

        $excluded = $this->seoConfig->getHreflangExcludedStoreIds();
        $map      = [];

        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int) $store->getId();
            if (!$store->getIsActive() || \in_array($storeId, $excluded, true)) {
                continue;
            }

            $localeCode = (string) $this->scopeConfig->getValue(
                'general/locale/code',
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            if ($localeCode === '') {
                continue;
            }

            $locale = $this->formatLocale($localeCode);
            $map[$storeId] = [
                'base_url' => rtrim((string) $store->getBaseUrl(), '/'),
                'locale'   => $locale,
                'language' => $this->extractLanguage($locale),
            ];
        }

        $this->map = $map;
        return $this->map;
    }

    /**
     * Convert a Magento locale code to a BCP 47 tag ('en_GB' → 'en-GB').
     *
     * @param string $magentoLocale
     * @return string
     */
    public function formatLocale(string $magentoLocale): string
    {
        return str_replace('_', '-', $magentoLocale);
    }

    /**
     * Extract the base language from a BCP 47 locale ('en-GB' → 'en').
     *
     * @param string $bcp47Locale
     * @return string
     */
    public function extractLanguage(string $bcp47Locale): string
    {
        return explode('-', $bcp47Locale)[0];
    }
}
