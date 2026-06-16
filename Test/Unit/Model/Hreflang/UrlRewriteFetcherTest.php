<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Hreflang;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use MageOS\Seo\Model\Hreflang\UrlRewriteFetcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UrlRewriteFetcherTest extends TestCase
{
    /**
     * @var AdapterInterface&MockObject
     */
    private AdapterInterface&MockObject $connection;

    /**
     * @var UrlRewriteFetcher
     */
    private UrlRewriteFetcher $fetcher;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(AdapterInterface::class);
        $select           = $this->createMock(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $this->connection->method('getTableName')->willReturn('url_rewrite');
        $this->connection->method('select')->willReturn($select);

        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($this->connection);

        $this->fetcher = new UrlRewriteFetcher($resource);
    }

    public function testReturnsPathsKeyedByStore(): void
    {
        $this->connection->method('fetchAll')->willReturn([
            ['store_id' => '1', 'request_path' => 'uk-path'],
            ['store_id' => '2', 'request_path' => 'us-path'],
        ]);
        $this->assertSame(
            [1 => 'uk-path', 2 => 'us-path'],
            $this->fetcher->fetchForEntity('product', 5)
        );
    }

    public function testFirstRowPerStoreWins(): void
    {
        $this->connection->method('fetchAll')->willReturn([
            ['store_id' => '1', 'request_path' => 'current'],
            ['store_id' => '1', 'request_path' => 'old-history'],
        ]);
        $this->assertSame([1 => 'current'], $this->fetcher->fetchForEntity('product', 5));
    }

    public function testReturnsEmptyWhenNoRewrites(): void
    {
        $this->connection->method('fetchAll')->willReturn([]);
        $this->assertSame([], $this->fetcher->fetchForEntity('cms-page', 9));
    }

    public function testFetchAllForTypeReturnsEmptyWithoutStores(): void
    {
        $this->assertSame([], $this->fetcher->fetchAllForType('product', []));
    }

    public function testFetchAllForTypeGroupsByEntityThenStore(): void
    {
        $this->connection->method('fetchAll')->willReturn([
            ['entity_id' => '5', 'store_id' => '1', 'request_path' => 'a'],
            ['entity_id' => '5', 'store_id' => '2', 'request_path' => 'a-de'],
            ['entity_id' => '6', 'store_id' => '1', 'request_path' => 'b'],
        ]);

        $this->assertSame(
            [5 => [1 => 'a', 2 => 'a-de'], 6 => [1 => 'b']],
            $this->fetcher->fetchAllForType('product', [1, 2])
        );
    }

    public function testFetchAllForTypeFirstPathPerStoreWins(): void
    {
        $this->connection->method('fetchAll')->willReturn([
            ['entity_id' => '5', 'store_id' => '1', 'request_path' => 'current'],
            ['entity_id' => '5', 'store_id' => '1', 'request_path' => 'history'],
        ]);

        $this->assertSame([5 => [1 => 'current']], $this->fetcher->fetchAllForType('product', [1]));
    }
}
