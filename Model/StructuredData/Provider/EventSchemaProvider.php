<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\StructuredData\Provider;

use Magento\Framework\View\Layout;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Seo\Api\EventDataProviderInterface;
use MageOS\Seo\Api\StructuredDataProviderInterface;
use MageOS\Seo\Model\Pool\HandleMatcher;
use MageOS\Seo\Model\StructuredData\OrganisationId;

/**
 * Emits Event structured data from bridge-supplied event data.
 *
 * Collects events from every matching EventDataProviderInterface in the pool (empty by default) and
 * emits one Event node per event, each linking the store Organisation as organizer.
 */
class EventSchemaProvider implements StructuredDataProviderInterface
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
        $orgId         = $this->organisationId->getId($storeId);

        $schemas = [];
        foreach ($this->dataProviders as $provider) {
            if (!$provider instanceof EventDataProviderInterface) {
                continue;
            }
            if (!$this->handleMatcher->matches($provider->getHandles(), $activeHandles)) {
                continue;
            }
            foreach ($provider->getEvents($storeId) as $event) {
                if (!empty($event['name']) && !empty($event['startDate'])) {
                    $schemas[] = $this->build($event, $orgId);
                }
            }
        }

        return $schemas;
    }

    /**
     * Build an Event node from event data, adding context and organizer reference.
     *
     * @param array<string,mixed> $event
     * @param string $orgId
     * @return array<string, mixed>
     */
    private function build(array $event, string $orgId): array
    {
        return array_merge(
            [
                '@context'  => 'https://schema.org',
                '@type'     => 'Event',
                'organizer' => ['@id' => $orgId],
            ],
            $event
        );
    }
}
