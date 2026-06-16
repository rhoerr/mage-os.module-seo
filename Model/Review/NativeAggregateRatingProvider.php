<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Review;

use Magento\Framework\App\ResourceConnection;
use MageOS\Seo\Api\AggregateRatingProviderInterface;

/**
 * Aggregate rating from Magento's native, pre-aggregated review_entity_summary table.
 *
 * Low priority so any third-party review bridge overrides it. Returns null when a product has no
 * reviews so a zero-review AggregateRating node is never emitted.
 */
class NativeAggregateRatingProvider implements AggregateRatingProviderInterface
{
    /**
     * Review entity type id for products in review_entity / review_entity_summary.
     */
    private const ENTITY_TYPE_PRODUCT = 1;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getRating(int $productId, int $storeId): ?array
    {
        $connection = $this->resourceConnection->getConnection();
        $table      = $connection->getTableName('review_entity_summary');

        $row = $connection->fetchRow(
            $connection->select()
                ->from($table, ['rating_summary', 'reviews_count'])
                ->where('entity_pk_value = ?', $productId)
                ->where('store_id = ?', $storeId)
                ->where('entity_type = ?', self::ENTITY_TYPE_PRODUCT)
        );

        if (!\is_array($row) || (int) ($row['reviews_count'] ?? 0) < 1) {
            return null;
        }

        // rating_summary is a 0–100 percentage; convert to a 5-star scale.
        $ratingValue = round(((float) $row['rating_summary']) / 20, 1);

        return [
            'ratingValue' => (string) $ratingValue,
            'reviewCount' => (string) (int) $row['reviews_count'],
            'bestRating'  => '5',
            'worstRating' => '1',
        ];
    }

    /**
     * @inheritdoc
     */
    public function getPriority(): int
    {
        return 100;
    }
}
