<?php

declare(strict_types=1);

namespace MageOS\Seo\Controller\Wellknown;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Exception\LocalizedException;
use MageOS\Seo\Model\WellKnown\EndpointPool;
use Psr\Log\LoggerInterface;

/**
 * Dispatches /.well-known/* requests to the matching registered endpoint.
 *
 * Returns 404 when the endpoint is unknown or disabled, and 500 (logged) when an endpoint fails
 * to render — for UCP this guards against ever serving a manifest with a leaked private key.
 */
class Index implements HttpGetActionInterface
{
    /**
     * @param EndpointPool $endpointPool
     * @param RawFactory $rawFactory
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly EndpointPool $endpointPool,
        private readonly RawFactory $rawFactory,
        private readonly RequestInterface $request,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Render the requested well-known document.
     *
     * @return Raw
     */
    public function execute(): Raw
    {
        $result = $this->rawFactory->create();
        $name = (string) $this->request->getParam('endpoint');
        $endpoint = $this->endpointPool->get($name);

        if ($endpoint === null || !$endpoint->isEnabled()) {
            $result->setHttpResponseCode(404);
            $result->setContents('');
            return $result;
        }

        try {
            $body = $endpoint->render();
        } catch (LocalizedException $e) {
            $this->logger->error('MageOS_Seo well-known endpoint failed: ' . $e->getMessage());
            $result->setHttpResponseCode(500);
            $result->setContents('');
            return $result;
        }

        $result->setHttpResponseCode(200);
        $result->setHeader('Content-Type', $endpoint->getContentType(), true);
        $result->setHeader('Cache-Control', $endpoint->getCacheControl(), true);
        $result->setContents($body);

        return $result;
    }
}
