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
}
