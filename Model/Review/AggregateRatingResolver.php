<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Review;

use MageOS\Seo\Api\AggregateRatingProviderInterface;

/**
 * Priority resolver for the aggregate rating provider pool.
 *
 * Returns the rating from the highest-priority provider that has data for the product, or null when
 * no provider does. This lets multiple review systems coexist (native + bridge vendors) and be
 * prioritised rather than mutually exclusive.
 */
class AggregateRatingResolver
{
    /**
     * @param array<mixed> $providers
     */
    public function __construct(
        private readonly array $providers = []
    ) {
    }

    /**
     * Resolve the winning aggregate rating for a product, or null if none is available.
     *
     * @param int $productId
     * @param int $storeId
     * @return array<string, string>|null
     */
    public function resolve(int $productId, int $storeId): ?array
    {
        $candidates = [];
        foreach ($this->providers as $provider) {
            if (!$provider instanceof AggregateRatingProviderInterface) {
                continue;
            }
            $rating = $provider->getRating($productId, $storeId);
            if (!empty($rating)) {
                $candidates[] = ['rating' => $rating, 'priority' => $provider->getPriority()];
            }
        }

        if (empty($candidates)) {
            return null;
        }

        usort($candidates, static fn (array $a, array $b): int => $b['priority'] <=> $a['priority']);

        return $candidates[0]['rating'];
    }
}
