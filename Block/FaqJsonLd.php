<?php

declare(strict_types=1);

namespace MageOS\Seo\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Seo\Api\FaqCollectorInterface;
use MageOS\Seo\Model\Config;
use MageOS\Seo\Model\Faq\SourcePool;

/**
 * Emits one FAQPage JSON-LD node for all FAQ groups rendered on the page.
 *
 * Rendered late (end of body) so every visible FAQ element has already registered its group with
 * the collector. Re-resolves the collected identifiers (rather than trusting render-time data) so
 * the schema stays correct even if an element's HTML was block-cached, and dedupes questions.
 */
class FaqJsonLd extends Template
{
    /**
     * @param Context $context
     * @param FaqCollectorInterface $collector
     * @param SourcePool $sourcePool
     * @param StoreManagerInterface $storeManager
     * @param Config $seoConfig
     * @param mixed[] $data
     */
    public function __construct(
        Context                                $context,
        private readonly FaqCollectorInterface $collector,
        private readonly SourcePool            $sourcePool,
        private readonly StoreManagerInterface $storeManager,
        private readonly Config                $seoConfig,
        array                                  $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Build the FAQPage JSON-LD string, or empty string when there is nothing to emit.
     *
     * @return string
     */
    public function getJsonLd(): string
    {
        if (!$this->seoConfig->isStructuredDataEnabled()) {
            return '';
        }

        $identifiers = $this->collector->getIdentifiers();
        if ($identifiers === []) {
            return '';
        }

        $storeId    = (int) $this->storeManager->getStore()->getId();
        $seen       = [];
        $mainEntity = [];
        foreach ($identifiers as $identifier) {
            foreach ($this->sourcePool->getFaqs($identifier, $storeId) as $faq) {
                $key = mb_strtolower(trim($faq['question']));
                if ($key === '' || isset($seen[$key])) {
                    continue;
                }
                $seen[$key]   = true;
                $mainEntity[] = [
                    '@type'          => 'Question',
                    'name'           => $faq['question'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
                ];
            }
        }

        if ($mainEntity === []) {
            return '';
        }

        $json = json_encode(
            ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $mainEntity],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
        if ($json === false) {
            return '';
        }

        // XSS protection: prevent </script> and HTML comment breakouts.
        return str_replace(['</', '<!--'], ['<\/', '<\!--'], $json);
    }

    /**
     * Render nothing when there is no FAQ schema to output.
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        if ($this->getJsonLd() === '') {
            return '';
        }
        return parent::_toHtml();
    }
}
