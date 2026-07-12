<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Feed;

use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Seo\Model\Config;
use MageOS\Seo\Model\Hreflang\SitemapGenerator;
use MageOS\Seo\Model\Hreflang\StoreLocaleMap;
use MageOS\Seo\Model\LlmsJsonl\JsonlBuilder;
use MageOS\Seo\Model\LlmsTxt\LlmsTxtBuilder;
use Psr\Log\LoggerInterface;

/**
 * Builds the pre-generated SEO feeds to storage for every active store view.
 *
 * Shared by the nightly cron (full rebuild) and the queue consumer (single feed
 * group after an invalidation). Whole-catalog builds therefore happen only in
 * background processes, never inside anonymous web requests.
 */
class FeedRegenerator
{
    public const GROUP_LLMS     = 'llms';
    public const GROUP_JSONL    = 'jsonl';
    public const GROUP_HREFLANG = 'hreflang';

    public const GROUPS = [self::GROUP_LLMS, self::GROUP_JSONL, self::GROUP_HREFLANG];

    /**
     * @param StoreManagerInterface $storeManager
     * @param Emulation $emulation
     * @param Config $seoConfig
     * @param LlmsTxtBuilder $llmsTxtBuilder
     * @param JsonlBuilder $jsonlBuilder
     * @param SitemapGenerator $sitemapGenerator
     * @param StoreLocaleMap $storeLocaleMap
     * @param FeedStorage $feedStorage
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly Emulation             $emulation,
        private readonly Config                $seoConfig,
        private readonly LlmsTxtBuilder        $llmsTxtBuilder,
        private readonly JsonlBuilder          $jsonlBuilder,
        private readonly SitemapGenerator      $sitemapGenerator,
        private readonly StoreLocaleMap        $storeLocaleMap,
        private readonly FeedStorage           $feedStorage,
        private readonly LoggerInterface       $logger
    ) {
    }

    /**
     * Regenerate one feed group (or all, when null) for every active store view.
     *
     * @param string|null $group One of self::GROUPS, or null for all
     * @return void
     */
    public function regenerate(?string $group = null): void
    {
        foreach ($this->storeManager->getStores() as $store) {
            if (!$store->getIsActive()) {
                continue;
            }
            $storeId = (int) $store->getId();

            $this->emulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
            try {
                $this->generateForStore($storeId, $group);
            } catch (\Throwable $e) {
                $this->logger->error(
                    \sprintf('MageOS_Seo: feed regeneration failed for store %d: %s', $storeId, $e->getMessage()),
                    ['exception' => $e, 'group' => $group]
                );
            } finally {
                // The locale map memoises per store scope; reset between emulations.
                $this->storeLocaleMap->reset();
                $this->emulation->stopEnvironmentEmulation();
            }
        }
    }

    /**
     * Generate the requested feeds for the currently emulated store.
     *
     * @param int $storeId
     * @param string|null $group
     * @throws \Magento\Framework\Exception\FileSystemException
     * @return void
     */
    private function generateForStore(int $storeId, ?string $group): void
    {
        if ($group === null || $group === self::GROUP_LLMS) {
            if ($this->seoConfig->isLlmsTxtEnabled($storeId)) {
                $this->feedStorage->write('llms.txt', $storeId, $this->llmsTxtBuilder->buildConcise());
            }
            if ($this->seoConfig->isLlmsFullTxtEnabled($storeId)) {
                $this->feedStorage->write('llms-full.txt', $storeId, $this->llmsTxtBuilder->buildFull());
            }
        }

        if (($group === null || $group === self::GROUP_JSONL)
            && $this->seoConfig->isLlmsJsonlEnabled($storeId)
        ) {
            $this->feedStorage->write('llms.jsonl', $storeId, $this->jsonlBuilder->build());
        }

        if (($group === null || $group === self::GROUP_HREFLANG)
            && $this->seoConfig->isHreflangEnabled($storeId)
            && $this->seoConfig->isHreflangSitemapEnabled()
            && \count($this->storeLocaleMap->getMap()) >= 2
        ) {
            // Remove this store's stale chunks first: the chunk count can shrink
            // between runs. Per-store only — other stores' files are current.
            $this->feedStorage->deleteForStore('hreflang-sitemap*.xml', $storeId);
            $baseUrl = (string) $this->storeManager->getStore()->getBaseUrl();
            foreach ($this->sitemapGenerator->generateFiles($baseUrl) as $fileName => $xml) {
                $this->feedStorage->write($fileName, $storeId, $xml);
            }
        }
    }
}
