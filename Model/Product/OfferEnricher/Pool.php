<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Product\OfferEnricher;

use Magento\Catalog\Api\Data\ProductInterface;
use MageOS\Seo\Api\OfferEnricherInterface;

/**
 * Collect-all pool for Offer enrichers.
 *
 * Merges every registered enricher's fragment into a single Offer-additions array. Enrichers are
 * applied in ascending sortOrder so higher-sortOrder enrichers win on conflicting keys.
 */
class Pool
{
    /**
     * @param array<mixed> $enrichers
     */
    public function __construct(
        private readonly array $enrichers = []
    ) {
    }

    /**
     * Collect and merge Offer additions from all enrichers for a product.
     *
     * @param ProductInterface $product
     * @param int $storeId
     * @return array<string, mixed>
     */
    public function enrich(ProductInterface $product, int $storeId): array
    {
        $valid = [];
        foreach ($this->enrichers as $enricher) {
            if ($enricher instanceof OfferEnricherInterface) {
                $valid[] = $enricher;
            }
        }

        usort($valid, static fn (OfferEnricherInterface $a, OfferEnricherInterface $b): int
            => $a->getSortOrder() <=> $b->getSortOrder());

        $fragments = [];
        foreach ($valid as $enricher) {
            $fragment = $enricher->enrich($product, $storeId);
            if (!empty($fragment)) {
                $fragments[] = $fragment;
            }
        }

        return $fragments === [] ? [] : array_merge(...$fragments);
    }
}
