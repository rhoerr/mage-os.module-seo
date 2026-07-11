<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\LlmsJsonl;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Helper\Stock as StockHelper;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Seo\Api\JsonlLineProviderInterface;

/**
 * Builds the /llms.jsonl document: one JSON-LD Product node per line for the store's catalog, plus
 * any lines contributed by bridge JsonlLineProviderInterface implementations.
 *
 * The catalog is processed in pages with URL rewrites and stock status loaded per page, so large
 * catalogs neither exhaust memory nor trigger per-product lookup queries.
 */
class JsonlBuilder
{
    private const PAGE_SIZE = 1000;

    /**
     * @param CollectionFactory $collectionFactory
     * @param ProductLineBuilder $productLineBuilder
     * @param StoreManagerInterface $storeManager
     * @param StockHelper $stockHelper
     * @param array<mixed> $lineProviders
     */
    public function __construct(
        private readonly CollectionFactory      $collectionFactory,
        private readonly ProductLineBuilder     $productLineBuilder,
        private readonly StoreManagerInterface  $storeManager,
        private readonly StockHelper            $stockHelper,
        private readonly array                  $lineProviders = []
    ) {
    }

    /**
     * Build the NDJSON document for the current store.
     *
     * @return string
     */
    public function build(): string
    {
        $storeId = (int) $this->storeManager->getStore()->getId();

        $collection = $this->collectionFactory->create();
        $collection->setStoreId($storeId);
        $collection->addStoreFilter($storeId);
        $collection->addAttributeToSelect(['name', 'short_description', 'description', 'sku', 'image']);
        $collection->addAttributeToFilter('status', ['eq' => Status::STATUS_ENABLED]);
        $collection->addAttributeToFilter('visibility', [
            'in' => [Visibility::VISIBILITY_IN_CATALOG, Visibility::VISIBILITY_BOTH],
        ]);
        $collection->addFinalPrice();
        // Loads request paths with the collection so getProductUrl() never falls back
        // to a per-product url_rewrite lookup.
        $collection->addUrlRewrite();
        $collection->setPageSize(self::PAGE_SIZE);

        $output   = '';
        $lastPage = $collection->getLastPageNumber();

        for ($page = 1; $page <= $lastPage; $page++) {
            $collection->setCurPage($page);
            $collection->clear();
            // Sets is_salable on every item in one pass instead of per-product
            // salability resolution inside the line builder.
            $this->stockHelper->addStockStatusToProducts($collection);

            foreach ($collection as $product) {
                if (!$product instanceof ProductInterface) {
                    continue;
                }
                $line = $this->encode($this->productLineBuilder->build($product));
                if ($line !== '') {
                    $output .= $line . "\n";
                }
            }
        }

        foreach ($this->lineProviders as $provider) {
            if (!$provider instanceof JsonlLineProviderInterface) {
                continue;
            }
            foreach ($provider->getAdditionalLines($storeId) as $node) {
                $line = $this->encode($node);
                if ($line !== '') {
                    $output .= $line . "\n";
                }
            }
        }

        return $output;
    }

    /**
     * Encode one node to a compact JSON line, or empty string on failure.
     *
     * @param array<string,mixed> $node
     * @return string
     */
    private function encode(array $node): string
    {
        $json = json_encode($node, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $json === false ? '' : $json;
    }
}
