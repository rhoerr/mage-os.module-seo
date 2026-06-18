<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Hreflang;

use MageOS\Seo\Model\Config;

/**
 * Turns a set of region links into the full hreflang alternate set.
 *
 * Shared by ResolverPool (head tags) and SitemapGenerator (sitemap) so the language-only,
 * x-default and single-store rules live in one place. Returns [] when fewer than two distinct
 * locales are present (single store — hreflang adds no value).
 */
class AlternateBuilder
{
    /**
     * @param Config $seoConfig
     */
    public function __construct(
        private readonly Config $seoConfig
    ) {
    }

    /**
     * Build the full ordered alternate set (region + language-only + x-default), or [].
     *
     * @param array<int,array{hreflang:string,url:string,store_id:int}> $regionLinks
     * @return array<int, array{hreflang: string, url: string}>
     */
    public function build(array $regionLinks): array
    {
        if (\count(array_unique(array_column($regionLinks, 'hreflang'))) < 2) {
            return [];
        }

        $output = [];
        foreach ($regionLinks as $link) {
            $output[] = ['hreflang' => $link['hreflang'], 'url' => $link['url']];
        }

        if ($this->seoConfig->isHreflangLanguageOnlyEnabled()) {
            foreach ($this->buildLanguageOnlyTags($regionLinks) as $tag) {
                $output[] = $tag;
            }
        }

        $xDefault = $this->buildXDefault($regionLinks);
        if ($xDefault !== null) {
            $output[] = $xDefault;
        }

        return $output;
    }

    /**
     * Build language-only tags for any base language served by exactly one store view.
     *
     * @param array<int,array{hreflang:string,url:string,store_id:int}> $regionLinks
     * @return array<int, array{hreflang: string, url: string}>
     */
    private function buildLanguageOnlyTags(array $regionLinks): array
    {
        $byLanguage = [];
        foreach ($regionLinks as $link) {
            $language = explode('-', $link['hreflang'])[0];
            $byLanguage[$language][] = $link;
        }

        $tags = [];
        foreach ($byLanguage as $language => $links) {
            if (\count($links) !== 1) {
                continue;
            }
            if ($links[0]['hreflang'] === $language) {
                continue;
            }
            $tags[] = ['hreflang' => $language, 'url' => $links[0]['url']];
        }

        return $tags;
    }

    /**
     * Build the x-default tag from the configured store view, or null if not configured/present.
     *
     * @param array<int,array{hreflang:string,url:string,store_id:int}> $regionLinks
     * @return array{hreflang: string, url: string}|null
     */
    private function buildXDefault(array $regionLinks): ?array
    {
        $xDefaultStoreId = $this->seoConfig->getHreflangXDefaultStoreId();
        if ($xDefaultStoreId <= 0) {
            return null;
        }

        foreach ($regionLinks as $link) {
            if ($link['store_id'] === $xDefaultStoreId) {
                return ['hreflang' => 'x-default', 'url' => $link['url']];
            }
        }

        return null;
    }
}
