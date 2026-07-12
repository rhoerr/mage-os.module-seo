<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Resolves the schema.org availability URI for a product via the MSI service
 * contracts (SKU-keyed salability per website stock), replacing the deprecated
 * CatalogInventory StockRegistry which ignores multi-source stock assignment.
 *
 * The resolved stock ID is memoised per website code, so a product page costs
 * one StockResolver lookup, and background feed generation iterating stores
 * under emulation stays correct across websites without a reset.
 */
class AvailabilityResolver implements ResetAfterRequestInterface
{
    public const IN_STOCK     = 'https://schema.org/InStock';
    public const OUT_OF_STOCK = 'https://schema.org/OutOfStock';
    public const BACKORDER    = 'https://schema.org/BackOrder';

    /**
     * @var array<string, int> website code => stock ID
     */
    private array $stockIds = [];

    /**
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param IsProductSalableInterface $isProductSalable
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     */
    public function __construct(
        private readonly StoreManagerInterface              $storeManager,
        private readonly StockResolverInterface             $stockResolver,
        private readonly IsProductSalableInterface          $isProductSalable,
        private readonly GetStockItemConfigurationInterface $getStockItemConfiguration
    ) {
    }

    /**
     * Resolve the schema.org availability URI for a product on the current website.
     *
     * Products the inventory APIs cannot answer for (no SKU, not assigned to a
     * stock/source) resolve to OutOfStock rather than erroring.
     *
     * @param ProductInterface $product
     * @return string
     */
    public function resolve(ProductInterface $product): string
    {
        $sku = (string) $product->getSku();
        if ($sku === '') {
            return self::OUT_OF_STOCK;
        }

        try {
            $stockId = $this->getCurrentStockId();
            if ($this->isProductSalable->execute($sku, $stockId)) {
                return self::IN_STOCK;
            }
            // Out of stock but backorderable: customers can still order.
            $configuration = $this->getStockItemConfiguration->execute($sku, $stockId);
            if ((int) $configuration->getBackorders() > 0) {
                return self::BACKORDER;
            }
        } catch (\Exception) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch -- fall through to default
        }

        return self::OUT_OF_STOCK;
    }

    /**
     * Resolve the MSI stock ID serving the current website (memoised per website).
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return int
     */
    public function getCurrentStockId(): int
    {
        $websiteCode = (string) $this->storeManager->getWebsite()->getCode();
        if (!isset($this->stockIds[$websiteCode])) {
            $this->stockIds[$websiteCode] = (int) $this->stockResolver
                ->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode)
                ->getStockId();
        }

        return $this->stockIds[$websiteCode];
    }

    /**
     * Drop the memoised stock IDs between worker-mode requests.
     *
     * @return void
     */
    public function _resetState(): void // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore -- framework interface
    {
        $this->stockIds = [];
    }
}
