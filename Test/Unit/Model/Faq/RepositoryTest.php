<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Faq;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use MageOS\Seo\Model\Faq\Repository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    /**
     * @var AdapterInterface&MockObject
     */
    private AdapterInterface&MockObject $connection;

    /**
     * @var Repository
     */
    private Repository $repository;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(AdapterInterface::class);
        $select           = $this->createMock(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $select->method('order')->willReturnSelf();
        $this->connection->method('getTableName')->willReturn('mage-os_seo_faq');
        $this->connection->method('select')->willReturn($select);

        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($this->connection);

        $this->repository = new Repository($resource);
    }

    public function testReturnsEmptyForBlankIdentifier(): void
    {
        $this->assertSame([], $this->repository->getByIdentifier('', 1));
    }

    public function testMapsRowsToQuestionAnswerPairs(): void
    {
        $this->connection->method('fetchAll')->willReturn([
            ['question' => 'Q1', 'answer' => 'A1'],
            ['question' => 'Q2', 'answer' => 'A2'],
        ]);

        $this->assertSame(
            [
                ['question' => 'Q1', 'answer' => 'A1'],
                ['question' => 'Q2', 'answer' => 'A2'],
            ],
            $this->repository->getByIdentifier('shipping', 1)
        );
    }

    public function testReturnsEmptyWhenNoRows(): void
    {
        $this->connection->method('fetchAll')->willReturn([]);
        $this->assertSame([], $this->repository->getByIdentifier('shipping', 1));
    }
}
