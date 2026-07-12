<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Feed;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * 301-redirects feed requests back to their canonical path.
 *
 * The feed routers match on path only, but the FPC/Varnish cache key includes the
 * query string — so /llms.txt?x=1, ?x=2, … would each be a separate cache miss
 * regenerating the document (a cheap amplification loop against expensive builders).
 * The same applies to the standard-router URLs (/mageos-seo/llms/index), which would
 * otherwise be duplicate-content URLs with their own cache entries.
 */
class CanonicalPathRedirect
{
    /**
     * @param RequestInterface $request
     * @param RedirectFactory $redirectFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly RequestInterface      $request,
        private readonly RedirectFactory       $redirectFactory,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Return a 301 redirect to the canonical feed path, or null when already canonical.
     *
     * @param string $canonicalPath Path relative to the store base URL, e.g. "llms.txt"
     * @return Redirect|null
     */
    public function check(string $canonicalPath): ?Redirect
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        $request  = $this->request;
        $pathInfo = trim($request->getPathInfo(), '/');
        $query    = $request->getQuery()->toArray();

        if ($query === [] && $pathInfo === trim($canonicalPath, '/')) {
            return null;
        }

        // Base URL keeps the store code when "Add Store Code to URLs" is enabled.
        $url = $this->storeManager->getStore()->getBaseUrl() . ltrim($canonicalPath, '/');

        return $this->redirectFactory->create()
            ->setUrl($url)
            ->setHttpResponseCode(301);
    }
}
