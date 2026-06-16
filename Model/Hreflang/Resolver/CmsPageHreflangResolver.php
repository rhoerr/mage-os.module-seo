<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Hreflang\Resolver;

use Magento\Framework\App\RequestInterface;
use MageOS\Seo\Api\HreflangResolverInterface;
use MageOS\Seo\Model\Cms\CmsPageResolver;
use MageOS\Seo\Model\Hreflang\LinkBuilder;
use MageOS\Seo\Model\Hreflang\StoreLocaleMap;

/**
 * Hreflang alternates for CMS pages, including the home page.
 *
 * The home page has no usable URL rewrite (it is served at the store root), so each store's base URL
 * is used directly. Other CMS pages resolve through their cms-page URL rewrites.
 */
class CmsPageHreflangResolver implements HreflangResolverInterface
{
    /**
     * @param CmsPageResolver $cmsPageResolver
     * @param RequestInterface $request
     * @param StoreLocaleMap $storeLocaleMap
     * @param LinkBuilder $linkBuilder
     */
    public function __construct(
        private readonly CmsPageResolver  $cmsPageResolver,
        private readonly RequestInterface $request,
        private readonly StoreLocaleMap   $storeLocaleMap,
        private readonly LinkBuilder      $linkBuilder
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getHandles(): array
    {
        return ['cms_page_view', 'cms_index_index'];
    }

    /**
     * @inheritdoc
     */
    public function getLinks(): array
    {
        if ($this->isHomePage()) {
            return $this->homeLinks();
        }

        $page = $this->cmsPageResolver->resolve();
        if ($page === null) {
            return [];
        }

        return $this->linkBuilder->build('cms-page', (int) $page->getId());
    }

    /**
     * Whether the current request is the store home page.
     *
     * @return bool
     */
    private function isHomePage(): bool
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $this->request;
        return trim($request->getPathInfo(), '/') === '';
    }

    /**
     * Build home-page alternates directly from each store's base URL.
     *
     * @return array<int, array{hreflang: string, url: string, store_id: int}>
     */
    private function homeLinks(): array
    {
        $links = [];
        foreach ($this->storeLocaleMap->getMap() as $storeId => $data) {
            $links[] = [
                'hreflang' => $data['locale'],
                'url'      => $data['base_url'] . '/',
                'store_id' => $storeId,
            ];
        }

        return $links;
    }
}
