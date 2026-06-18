<?php

declare(strict_types=1);

namespace MageOS\Seo\Controller\Llmsjsonl;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use MageOS\Seo\Model\Config;
use MageOS\Seo\Model\LlmsJsonl\JsonlBuilder;

/**
 * Serves /llms.jsonl — one JSON-LD Product node per line (NDJSON) for AI catalog consumers.
 */
class Index implements HttpGetActionInterface
{
    /**
     * @param JsonlBuilder $builder
     * @param RawFactory $rawFactory
     * @param Config $seoConfig
     */
    public function __construct(
        private readonly JsonlBuilder $builder,
        private readonly RawFactory   $rawFactory,
        private readonly Config       $seoConfig
    ) {
    }

    /**
     * Serve the NDJSON catalog with cache headers.
     *
     * @return Raw
     */
    public function execute(): Raw
    {
        $result = $this->rawFactory->create();

        if (!$this->seoConfig->isLlmsJsonlEnabled()) {
            $result->setHttpResponseCode(404);
            $result->setContents('');
            return $result;
        }

        $result->setHttpResponseCode(200);
        $result->setHeader('Content-Type', 'application/x-ndjson; charset=utf-8', true);
        $result->setHeader('Cache-Control', 'public, max-age=3600, s-maxage=86400', true);
        $result->setHeader('X-Magento-Tags', 'RS_LLMS_JSONL', true);
        $result->setContents($this->builder->build());

        return $result;
    }
}
