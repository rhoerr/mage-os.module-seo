<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Hreflang;

/**
 * Builds the /hreflang-sitemap.xml document.
 *
 * Three bulk queries (product, category, cms-page) plus the home pages cover the whole catalogue.
 * Every entity emits one <url> block per store view it exists on, each carrying the full
 * <xhtml:link> alternate set (region links + language-only + x-default) — the layout Google
 * requires for a hreflang sitemap.
 */
class SitemapGenerator
{
    private const ENTITY_TYPES = ['product', 'category', 'cms-page'];

    /**
     * @param StoreLocaleMap $storeLocaleMap
     * @param UrlRewriteFetcher $urlRewriteFetcher
     * @param LinkBuilder $linkBuilder
     * @param AlternateBuilder $alternateBuilder
     */
    public function __construct(
        private readonly StoreLocaleMap    $storeLocaleMap,
        private readonly UrlRewriteFetcher $urlRewriteFetcher,
        private readonly LinkBuilder       $linkBuilder,
        private readonly AlternateBuilder  $alternateBuilder
    ) {
    }

    /**
     * Generate the full hreflang sitemap XML.
     *
     * @return string
     */
    public function generate(): string
    {
        $map      = $this->storeLocaleMap->getMap();
        $storeIds = array_keys($map);

        $blocks = $this->entityBlocks($this->homeRegionLinks($map));

        foreach (self::ENTITY_TYPES as $entityType) {
            foreach ($this->urlRewriteFetcher->fetchAllForType($entityType, $storeIds) as $paths) {
                foreach ($this->entityBlocks($this->linkBuilder->buildFromPaths($paths)) as $block) {
                    $blocks[] = $block;
                }
            }
        }

        return $this->wrap($blocks);
    }

    /**
     * Home-page region links built directly from each store's base URL.
     *
     * @param array<int,array{base_url:string,locale:string,language:string}> $map
     * @return array<int, array{hreflang: string, url: string, store_id: int}>
     */
    private function homeRegionLinks(array $map): array
    {
        $links = [];
        foreach ($map as $storeId => $data) {
            $links[] = [
                'hreflang' => $data['locale'],
                'url'      => $data['base_url'] . '/',
                'store_id' => $storeId,
            ];
        }

        return $links;
    }

    /**
     * Build one <url> block per store view for an entity, each with the full alternate set.
     *
     * @param array<int,array{hreflang:string,url:string,store_id:int}> $regionLinks
     * @return string[]
     */
    private function entityBlocks(array $regionLinks): array
    {
        $alternates = $this->alternateBuilder->build($regionLinks);
        if ($alternates === []) {
            return [];
        }

        $blocks = [];
        foreach ($regionLinks as $link) {
            $blocks[] = $this->renderUrlBlock($link['url'], $alternates);
        }

        return $blocks;
    }

    /**
     * Render a single <url> block.
     *
     * @param string $loc
     * @param array<int,array{hreflang:string,url:string}> $alternates
     * @return string
     */
    private function renderUrlBlock(string $loc, array $alternates): string
    {
        $lines = ['  <url>', '    <loc>' . $this->escape($loc) . '</loc>'];
        foreach ($alternates as $alternate) {
            $lines[] = \sprintf(
                '    <xhtml:link rel="alternate" hreflang="%s" href="%s"/>',
                $this->escape($alternate['hreflang']),
                $this->escape($alternate['url'])
            );
        }
        $lines[] = '  </url>';

        return implode("\n", $lines);
    }

    /**
     * Wrap the url blocks in the urlset document.
     *
     * @param string[] $blocks
     * @return string
     */
    private function wrap(array $blocks): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n"
            . '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n"
            . ($blocks === [] ? '' : implode("\n", $blocks) . "\n")
            . '</urlset>' . "\n";
    }

    /**
     * Escape a value for safe inclusion in XML.
     *
     * @param string $value
     * @return string
     */
    private function escape(string $value): string
    {
        // Native escaping with ENT_XML1 is required for valid sitemap XML output.
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.DiscouragedWithAlternative
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
