<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Hreflang;

/**
 * Builds hreflang link entries for an entity from its URL rewrites and the store locale map.
 *
 * Shared by the per-page-type resolvers so the rewrite-to-link mapping lives in one place.
 */
class LinkBuilder
{
    /**
     * @param UrlRewriteFetcher $urlRewriteFetcher
     * @param StoreLocaleMap $storeLocaleMap
     */
    public function __construct(
        private readonly UrlRewriteFetcher $urlRewriteFetcher,
        private readonly StoreLocaleMap    $storeLocaleMap
    ) {
    }

    /**
     * Build alternate-link entries for one entity across the eligible store views.
     *
     * @param string $entityType
     * @param int $entityId
     * @return array<int, array{hreflang: string, url: string, store_id: int}>
     */
    public function build(string $entityType, int $entityId): array
    {
        return $this->buildFromPaths($this->urlRewriteFetcher->fetchForEntity($entityType, $entityId));
    }

    /**
     * Build alternate-link entries from an already-fetched store_id => request_path map.
     *
     * @param array<int,string> $paths
     * @return array<int, array{hreflang: string, url: string, store_id: int}>
     */
    public function buildFromPaths(array $paths): array
    {
        $map = $this->storeLocaleMap->getMap();

        $links = [];
        foreach ($paths as $storeId => $path) {
            if (!isset($map[$storeId])) {
                continue;
            }
            $links[] = [
                'hreflang' => $map[$storeId]['locale'],
                'url'      => $map[$storeId]['base_url'] . '/' . ltrim($path, '/'),
                'store_id' => $storeId,
            ];
        }

        return $links;
    }
}
