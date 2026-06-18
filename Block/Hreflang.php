<?php

declare(strict_types=1);

namespace MageOS\Seo\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use MageOS\Seo\Model\Config;
use MageOS\Seo\Model\Hreflang\ResolverPool;

/**
 * Renders <link rel="alternate" hreflang="…"> tags in <head>.
 *
 * Fully FPC-cacheable: output depends only on the URL. Renders nothing when hreflang is disabled or
 * the page has no useful alternate set (single store).
 */
class Hreflang extends Template
{
    /**
     * @param Context $context
     * @param ResolverPool $resolverPool
     * @param Config $seoConfig
     * @param mixed[] $data
     */
    public function __construct(
        Context                       $context,
        private readonly ResolverPool $resolverPool,
        private readonly Config        $seoConfig,
        array                          $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Return the hreflang link set for the current page.
     *
     * @return array<int, array{hreflang: string, url: string}>
     */
    public function getLinks(): array
    {
        if (!$this->seoConfig->isHreflangEnabled()) {
            return [];
        }

        return $this->resolverPool->getLinks();
    }

    /**
     * Render nothing when there are no alternate links.
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        if ($this->getLinks() === []) {
            return '';
        }

        return parent::_toHtml();
    }
}
