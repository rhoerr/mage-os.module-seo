<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Faq;

use MageOS\Seo\Api\FaqCollectorInterface;

/**
 * Request-scoped FAQ identifier collector (one shared instance per request, like SchemaRegistry).
 */
class Collector implements FaqCollectorInterface
{
    /**
     * @var array<string, true>
     */
    private array $identifiers = [];

    /**
     * @inheritdoc
     */
    public function collect(string $identifier): void
    {
        if ($identifier !== '') {
            $this->identifiers[$identifier] = true;
        }
    }

    /**
     * @inheritdoc
     */
    public function getIdentifiers(): array
    {
        return array_keys($this->identifiers);
    }
}
