<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Faq;

use Magento\Framework\App\ResourceConnection;

/**
 * Read access to FAQ entries by group identifier.
 *
 * Uses a direct connection query (consistent with the module's category/product override
 * repositories) so it is fast and unit-testable. Returns global (store 0) and store-specific rows
 * for the identifier, ordered by sort_order.
 */
class Repository
{
    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * Return active FAQ entries for a group identifier and store, ordered by sort order.
     *
     * @param string $identifier
     * @param int $storeId
     * @return array<int, array{question: string, answer: string}>
     */
    public function getByIdentifier(string $identifier, int $storeId): array
    {
        if ($identifier === '') {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $table      = $this->resourceConnection->getTableName('mageos_seo_faq');

        $rows = $connection->fetchAll(
            $connection->select()
                ->from($table, ['question', 'answer'])
                ->where('identifier = ?', $identifier)
                ->where('store_id IN (?)', [0, $storeId])
                ->where('is_active = ?', 1)
                ->order('sort_order ASC')
        );

        return array_map(
            static fn (array $row): array => [
                'question' => (string) $row['question'],
                'answer'   => (string) $row['answer'],
            ],
            $rows
        );
    }
}
