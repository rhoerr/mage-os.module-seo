<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Category;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use MageOS\Seo\Model\Category\ProductOverrideRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductOverrideRepositoryTest extends TestCase
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
        $this->resource->method('getTableName')->willReturn('mageos_seo_product_override');
    }

    public function testGetForProductMergesStoreRowOverGlobalAndMemoises(): void
    {
        $this->resource->method('getConnection')->willReturn($this->connection);
        $this->connection->expects($this->once())->method('fetchAll')->willReturn([
            ['store_id' => 0, 'override_fields' => '{"brand":"Acme","color":"Blue"}', 'robots_meta' => null],
            ['store_id' => 2, 'override_fields' => '{"color":"Red"}', 'robots_meta' => 'NOINDEX,FOLLOW'],
        ]);

        $repository = new ProductOverrideRepository($this->resource);
        $first      = $repository->getForProduct(10, 2);
        $second     = $repository->getForProduct(10, 2);

        $this->assertSame($first, $second);
        $this->assertSame('Acme', $first['override_fields']['brand']);
        $this->assertSame('Red', $first['override_fields']['color']);
        $this->assertSame('NOINDEX,FOLLOW', $first['robots_meta']);
    }

    public function testResetStateClearsTheMemoisedRows(): void
    {
        $this->resource->method('getConnection')->willReturn($this->connection);
        $this->connection->expects($this->exactly(2))->method('fetchAll')->willReturn([]);

        $repository = new ProductOverrideRepository($this->resource);
        $repository->getForProduct(10, 2);
        $repository->_resetState();
        $repository->getForProduct(10, 2);
    }

    public function testConnectionIsFetchedPerOperationAndNeverAtConstruction(): void
    {
        // ResourceConnection::_resetState() closes connections between worker-mode
        // requests, so the adapter must be fetched per operation, never at
        // construction — a constructor fetch would make this count three.
        $this->resource->expects($this->exactly(2))->method('getConnection')
            ->willReturn($this->connection);
        $this->connection->method('fetchAll')->willReturn([]);

        $repository = new ProductOverrideRepository($this->resource);
        $repository->getForProduct(10, 2);
        $repository->getForProduct(11, 2);
    }

    public function testSaveInvalidatesTheMemoisedRow(): void
    {
        $this->resource->method('getConnection')->willReturn($this->connection);
        $this->connection->expects($this->exactly(2))->method('fetchAll')->willReturn([]);

        $repository = new ProductOverrideRepository($this->resource);
        $repository->getForProduct(10, 2);
        $repository->save(10, 2, ['override_fields' => ['brand' => 'Acme']]);
        $repository->getForProduct(10, 2);
    }
}
