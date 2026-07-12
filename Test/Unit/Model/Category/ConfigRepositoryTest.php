<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Category;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use MageOS\Seo\Model\Category\ConfigRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigRepositoryTest extends TestCase
{
    /**
     * @var AdapterInterface&MockObject
     */
    private AdapterInterface&MockObject $connection;

    /**
     * @var ResourceConnection&MockObject
     */
    private ResourceConnection&MockObject $resource;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(AdapterInterface::class);
        $select           = $this->createMock(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $select->method('order')->willReturnSelf();
        $this->connection->method('select')->willReturn($select);

        $this->resource = $this->createMock(ResourceConnection::class);
        $this->resource->method('getTableName')->willReturn('mageos_seo_category_config');
    }

    public function testGetForCategoryMemoisesPerCategoryAndStore(): void
    {
        $this->resource->method('getConnection')->willReturn($this->connection);
        $this->connection->expects($this->once())->method('fetchRow')
            ->willReturn(['category_id' => 5, 'store_id' => 0, 'schema_template' => 'generic']);

        $repository = new ConfigRepository($this->resource);
        $first      = $repository->getForCategory(5);
        $second     = $repository->getForCategory(5);

        $this->assertSame($first, $second);
        $this->assertSame('generic', $first['schema_template']);
    }

    public function testResetStateClearsTheMemoisedRows(): void
    {
        $this->resource->method('getConnection')->willReturn($this->connection);
        $this->connection->expects($this->exactly(2))->method('fetchRow')
            ->willReturn(['category_id' => 5, 'store_id' => 0, 'schema_template' => 'generic']);

        $repository = new ConfigRepository($this->resource);
        $repository->getForCategory(5);
        $repository->_resetState();
        $repository->getForCategory(5);
    }

    public function testConnectionIsFetchedPerOperationAndNeverAtConstruction(): void
    {
        // ResourceConnection::_resetState() closes connections between worker-mode
        // requests, so the adapter must be fetched per operation, never at
        // construction — a constructor fetch would make this count three.
        $this->resource->expects($this->exactly(2))->method('getConnection')
            ->willReturn($this->connection);
        $this->connection->method('fetchRow')->willReturn([]);

        $repository = new ConfigRepository($this->resource);
        $repository->getForCategory(5);
        $repository->getForCategory(7);
    }

    public function testSaveInvalidatesTheMemoisedRow(): void
    {
        $this->resource->method('getConnection')->willReturn($this->connection);
        $this->connection->expects($this->exactly(2))->method('fetchRow')
            ->willReturn(['category_id' => 5, 'store_id' => 0, 'schema_template' => 'generic']);

        $repository = new ConfigRepository($this->resource);
        $repository->getForCategory(5);
        $repository->save(5, ['schema_template' => 'food']);
        $repository->getForCategory(5);
    }

    public function testStoreRowMergeLetsStoreValuesWinAndPreservesGlobalAndZero(): void
    {
        // storeId > 0 loads the global (store 0) and store rows and merges them:
        // store non-empty values win, a null store value keeps the global value,
        // and a legitimate 0 is preserved (not treated as "empty").
        $this->resource->method('getConnection')->willReturn($this->connection);
        $this->connection->method('fetchAll')->willReturn([
            ['category_id' => 5, 'store_id' => 0, 'schema_template' => 'generic',
                'robots_meta' => 'INDEX,FOLLOW', 'item_list_enabled' => 1],
            ['category_id' => 5, 'store_id' => 2, 'schema_template' => 'food',
                'robots_meta' => null, 'item_list_enabled' => 0],
        ]);

        $result = (new ConfigRepository($this->resource))->getForCategory(5, [], 2);

        $this->assertSame('food', $result['schema_template']);
        $this->assertSame('INDEX,FOLLOW', $result['robots_meta']);
        $this->assertSame(0, $result['item_list_enabled']);
    }

    public function testTemplateIsInheritedFromNearestAncestorKeepingOwnValues(): void
    {
        $this->resource->method('getConnection')->willReturn($this->connection);
        // First fetchRow = the category itself (no template); second = ancestor 5.
        $this->connection->method('fetchRow')->willReturnOnConsecutiveCalls(
            ['category_id' => 14, 'store_id' => 0, 'schema_template' => '', 'robots_meta' => 'NOINDEX'],
            ['category_id' => 5, 'store_id' => 0, 'schema_template' => 'generic'],
        );

        $result = (new ConfigRepository($this->resource))->getForCategory(14, ['1', '2', '5', '14'], 0);

        $this->assertSame('generic', $result['schema_template']);
        $this->assertSame('NOINDEX', $result['robots_meta']);
        $this->assertSame(14, $result['category_id']);
    }

    public function testRootCategoriesAreNotUsedAsTemplateAncestors(): void
    {
        // Ancestors 1 and 2 (root / default category) must be skipped, so no
        // template is inherited and the ancestor rows are never loaded.
        $this->resource->method('getConnection')->willReturn($this->connection);
        $this->connection->expects($this->once())->method('fetchRow')
            ->willReturn(['category_id' => 14, 'store_id' => 0, 'schema_template' => '']);

        $result = (new ConfigRepository($this->resource))->getForCategory(14, ['1', '2'], 0);

        $this->assertSame('', $result['schema_template']);
    }
}
