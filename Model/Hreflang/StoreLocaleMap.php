<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Hreflang;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Seo\Model\Config;

/**
 * Request-scoped map of active store views to their base URL and BCP 47 locale.
 *
 * Excluded and inactive stores are filtered here so downstream resolvers and the sitemap never see
 * them. Built once per request and memoised.
 */
class StoreLocaleMap implements ResetAfterRequestInterface
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
     * Scoped to the current website by default (config: hreflang/same_website_only)
     * and deduplicated by locale — two alternates with the same hreflang value are
     * invalid, so the lowest store ID deterministically wins per locale.
     *
     * @return array<int, array{base_url: string, locale: string, language: string}>
     */
    public function getMap(): array
    {
        if ($this->map !== null) {
            return $this->map;
        }

        $excluded  = $this->seoConfig->getHreflangExcludedStoreIds();
        $websiteId = $this->seoConfig->isHreflangSameWebsiteOnly()
            ? (int) $this->storeManager->getStore()->getWebsiteId()
            : null;

        // Sort by actual store ID (not array keys) so the dedupe winner below is
        // deterministic regardless of how the store list is keyed.
        $stores = array_values($this->storeManager->getStores());
        usort($stores, static fn ($a, $b): int => (int) $a->getId() <=> (int) $b->getId());

        $map         = [];
        $seenLocales = [];

        foreach ($stores as $store) {
            $storeId = (int) $store->getId();
            if (!$store->getIsActive() || \in_array($storeId, $excluded, true)) {
                continue;
            }
            if ($websiteId !== null && (int) $store->getWebsiteId() !== $websiteId) {
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
            if (isset($seenLocales[$locale])) {
                // Duplicate hreflang values are invalid; first (lowest ID) store wins.
                continue;
            }
            $seenLocales[$locale] = true;

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
     * Drop the memoised map so the next getMap() rebuilds for the current store scope.
     *
     * Needed by cron feed generation, which iterates stores under environment
     * emulation within a single process.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->map = null;
    }

    /**
     * Drop the memoised map between worker-mode requests (delegates to reset()).
     *
     * @return void
     */
    public function _resetState(): void // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore -- framework interface
    {
        $this->reset();
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
