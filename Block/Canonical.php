<?php

declare(strict_types=1);

namespace MageOS\Seo\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Seo\Model\Cms\CmsPageResolver;

/**
 * Outputs a canonical URL for CMS content pages (home page and cms_page_view).
 *
 * Product and category canonicals are core's job (catalog/seo/product_canonical_tag
 * and category_canonical_tag) or a bridge module's via CanonicalUrlManager — this
 * block deliberately stays off those pages. It also emits nothing on search, cart,
 * checkout, account and other non-indexable pages: canonicalising such URLs (or
 * echoing the request path back as its own canonical) legitimises duplicate URLs
 * instead of consolidating them.
 *
 * URLs are built from the configured store base URL — never from the client-supplied
 * Host header — so store codes survive and cache poisoning cannot skew the output.
 */
class Canonical extends Template
{
    private const HANDLE_HOME     = 'cms_index_index';
    private const HANDLE_CMS_PAGE = 'cms_page_view';

    /**
     * @param Context $context
     * @param CmsPageResolver $cmsPageResolver
     * @param StoreManagerInterface $storeManager
     * @param mixed[] $data
     */
    public function __construct(
        Context                                $context,
        private readonly CmsPageResolver       $cmsPageResolver,
        private readonly StoreManagerInterface $storeManager,
        array                                  $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Return the canonical URL for the current page, or empty string when none applies.
     *
     * @return string
     */
    public function getCanonicalUrl(): string
    {
        if ($this->hasExistingCanonical()) {
            return '';
        }

        try {
            $handles = $this->getLayout()->getUpdate()->getHandles();
            $baseUrl = rtrim((string) $this->storeManager->getStore()->getBaseUrl(), '/') . '/';

            if (\in_array(self::HANDLE_HOME, $handles, true)) {
                return $baseUrl;
            }

            if (\in_array(self::HANDLE_CMS_PAGE, $handles, true)) {
                $page       = $this->cmsPageResolver->resolve();
                $identifier = $page !== null ? (string) $page->getIdentifier() : '';
                if ($identifier !== '') {
                    return $baseUrl . $identifier;
                }
            }

            return '';
        } catch (\Exception) {
            return '';
        }
    }

    /**
     * Whether a canonical link asset has already been added by core or another module.
     *
     * Detected by asset content type — matching identifier prefixes would false-positive
     * on unrelated remote assets (font preloads, og images) and kill the fallback.
     *
     * @return bool
     */
    private function hasExistingCanonical(): bool
    {
        foreach ($this->pageConfig->getAssetCollection()->getAll() as $asset) {
            if ($asset->getContentType() === 'canonical') {
                return true;
            }
        }

        return false;
    }

    /**
     * Render nothing if no canonical URL is needed.
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        if ($this->getCanonicalUrl() === '') {
            return '';
        }
        return parent::_toHtml();
    }
}
