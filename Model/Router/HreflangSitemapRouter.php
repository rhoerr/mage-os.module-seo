<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Router;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;

/**
 * Intercepts /hreflang-sitemap.xml and forwards it to the sitemap controller without a URL rewrite.
 *
 * Registered before the default router. Mirrors the llms.txt router pattern.
 */
class HreflangSitemapRouter implements RouterInterface
{
    private const PATH = 'hreflang-sitemap.xml';

    /**
     * @param ActionFactory $actionFactory
     */
    public function __construct(
        private readonly ActionFactory $actionFactory
    ) {
    }

    /**
     * Forward /hreflang-sitemap.xml to the sitemap controller.
     *
     * @param RequestInterface $request
     * @return ActionInterface|null
     */
    public function match(RequestInterface $request): ?ActionInterface
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        if (trim($request->getPathInfo(), '/') !== self::PATH) {
            return null;
        }

        // Already forwarded by a previous iteration — avoid an infinite loop.
        if ($request->getModuleName() === 'rs-seo') {
            return null;
        }

        $request->setModuleName('rs-seo')
                ->setControllerName('hreflangsitemap')
                ->setActionName('index')
                ->setAlias(\Magento\Framework\Url::REWRITE_REQUEST_PATH_ALIAS, self::PATH);

        return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
    }
}
