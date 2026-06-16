<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\LlmsTxt;

use Magento\Store\Model\StoreManagerInterface;
use MageOS\Seo\Model\Faq\SourcePool;

/**
 * Injects the global FAQ group into /llms.txt and /llms-full.txt as a markdown Q&A section.
 *
 * Reads FAQs for the conventional "global" group identifier from the FAQ source pool. Page-specific
 * FAQs are intentionally excluded — llms.txt is a site-level summary.
 */
class FaqLlmsSectionProvider implements SectionProviderInterface
{
    private const GLOBAL_IDENTIFIER = 'global';
    private const CONCISE_LIMIT     = 5;

    /**
     * @param SourcePool $sourcePool
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly SourcePool            $sourcePool,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getConciseSection(): string
    {
        return $this->render(self::CONCISE_LIMIT);
    }

    /**
     * @inheritdoc
     */
    public function getFullSection(): string
    {
        return $this->render(0);
    }

    /**
     * Render the FAQ markdown section, optionally limited to the first $limit entries.
     *
     * @param int $limit 0 = no limit
     * @return string
     */
    private function render(int $limit): string
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        $faqs    = $this->sourcePool->getFaqs(self::GLOBAL_IDENTIFIER, $storeId);
        if ($faqs === []) {
            return '';
        }

        if ($limit > 0) {
            $faqs = \array_slice($faqs, 0, $limit);
        }

        $lines = ['## Frequently Asked Questions', ''];
        foreach ($faqs as $faq) {
            $lines[] = '**' . $faq['question'] . '**';
            $lines[] = $faq['answer'];
            $lines[] = '';
        }

        return rtrim(implode("\n", $lines));
    }
}
