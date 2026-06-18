<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\StructuredData\Provider;

use Magento\Framework\View\Layout;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Seo\Api\ArticleDataProviderInterface;
use MageOS\Seo\Api\StructuredDataProviderInterface;
use MageOS\Seo\Model\Pool\HandleMatcher;
use MageOS\Seo\Model\StructuredData\OrganisationId;

/**
 * Emits BlogPosting structured data from bridge-supplied article data.
 *
 * Iterates the registered ArticleDataProviderInterface pool (empty by default) and emits a node for
 * the first provider that has an article for the current page. Author and publisher both reference
 * the shared Organisation @id.
 */
class ArticleSchemaProvider implements StructuredDataProviderInterface
{
    /**
     * @var HandleMatcher
     */
    private readonly HandleMatcher $handleMatcher;

    /**
     * @param Layout $layout
     * @param StoreManagerInterface $storeManager
     * @param OrganisationId $organisationId
     * @param array<mixed> $dataProviders
     * @param HandleMatcher|null $handleMatcher
     */
    public function __construct(
        private readonly Layout                $layout,
        private readonly StoreManagerInterface $storeManager,
        private readonly OrganisationId        $organisationId,
        private readonly array                 $dataProviders = [],
        ?HandleMatcher $handleMatcher = null
    ) {
        $this->handleMatcher = $handleMatcher ?? new HandleMatcher();
    }

    /**
     * @inheritdoc
     */
    public function getHandles(): array
    {
        return ['*'];
    }

    /**
     * @inheritdoc
     */
    public function getSchemas(): array
    {
        $activeHandles = $this->layout->getUpdate()->getHandles();
        $storeId       = (int) $this->storeManager->getStore()->getId();

        foreach ($this->dataProviders as $provider) {
            if (!$provider instanceof ArticleDataProviderInterface) {
                continue;
            }
            if (!$this->handleMatcher->matches($provider->getHandles(), $activeHandles)) {
                continue;
            }
            $article = $provider->getArticle($storeId);
            if (!empty($article) && !empty($article['headline']) && !empty($article['url'])) {
                return [$this->build($article, $storeId)];
            }
        }

        return [];
    }

    /**
     * Build the BlogPosting node from article data.
     *
     * @param array<string,mixed> $article
     * @param int $storeId
     * @return array<string, mixed>
     */
    private function build(array $article, int $storeId): array
    {
        $orgId = $this->organisationId->getId($storeId);

        $node = [
            '@context'      => 'https://schema.org',
            '@type'         => 'BlogPosting',
            '@id'           => $article['url'] . '#article',
            'headline'      => $article['headline'],
            'url'           => $article['url'],
            'datePublished' => $article['datePublished'] ?? '',
            'author'        => ['@id' => $orgId],
            'publisher'     => ['@id' => $orgId],
        ];

        foreach (['description', 'dateModified', 'image'] as $field) {
            if (!empty($article[$field])) {
                $node[$field] = $article[$field];
            }
        }
        if (!empty($article['keywords'])) {
            $node['keywords'] = $article['keywords'];
        }

        return $node;
    }
}
