<?php

declare(strict_types=1);

namespace MageOS\Seo\Controller\Hreflangsitemap;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use MageOS\Seo\Model\Config;
use MageOS\Seo\Model\Hreflang\SitemapGenerator;
use MageOS\Seo\Model\Hreflang\StoreLocaleMap;

/**
 * Serves /hreflang-sitemap.xml.
 *
 * Returns 404 when hreflang or the sitemap is disabled, or when fewer than two store views are
 * eligible (a single store needs no hreflang sitemap).
 */
class Index implements HttpGetActionInterface
{
    /**
     * @param SitemapGenerator $sitemapGenerator
     * @param StoreLocaleMap $storeLocaleMap
     * @param RawFactory $rawFactory
     * @param Config $seoConfig
     */
    public function __construct(
        private readonly SitemapGenerator $sitemapGenerator,
        private readonly StoreLocaleMap   $storeLocaleMap,
        private readonly RawFactory       $rawFactory,
        private readonly Config           $seoConfig
    ) {
    }

    /**
     * Serve the hreflang sitemap XML with cache headers.
     *
     * @return Raw
     */
    public function execute(): Raw
    {
        $result = $this->rawFactory->create();

        $disabled = !$this->seoConfig->isHreflangEnabled() || !$this->seoConfig->isHreflangSitemapEnabled();
        if ($disabled || \count($this->storeLocaleMap->getMap()) < 2) {
            $result->setHttpResponseCode(404);
            $result->setContents('');
            return $result;
        }

        $result->setHttpResponseCode(200);
        $result->setHeader('Content-Type', 'application/xml; charset=utf-8', true);
        $result->setHeader('Cache-Control', 'public, max-age=86400, s-maxage=86400', true);
        $result->setHeader('X-Magento-Tags', 'RS_HREFLANG_SITEMAP', true);
        $result->setContents($this->sitemapGenerator->generate());

        return $result;
    }
}
