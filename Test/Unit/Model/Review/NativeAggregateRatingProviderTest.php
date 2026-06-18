<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Review;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use MageOS\Seo\Model\Review\NativeAggregateRatingProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NativeAggregateRatingProviderTest extends TestCase
{
    /**
     * @var AdapterInterface&MockObject
     */
    private AdapterInterface&MockObject $connection;

    /**
     * @var NativeAggregateRatingProvider
     */
    private NativeAggregateRatingProvider $provider;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(AdapterInterface::class);
        $select           = $this->createMock(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $this->connection->method('getTableName')->willReturn('review_entity_summary');
        $this->connection->method('select')->willReturn($select);

        $resourceConnection = $this->createMock(ResourceConnection::class);
        $resourceConnection->method('getConnection')->willReturn($this->connection);

        $this->provider = new NativeAggregateRatingProvider($resourceConnection);
    }

    public function testPriorityIsLowFallback(): void
    {
        $this->assertSame(100, $this->provider->getPriority());
    }

    public function testReturnsNullWhenNoRow(): void
    {
        $this->connection->method('fetchRow')->willReturn(false);
        $this->assertNull($this->provider->getRating(5, 1));
    }

    public function testReturnsNullWhenZeroReviews(): void
    {
        $this->connection->method('fetchRow')->willReturn(['rating_summary' => '100', 'reviews_count' => '0']);
        $this->assertNull($this->provider->getRating(5, 1));
    }

    public function testConvertsPercentageToFiveStarScale(): void
    {
        $this->connection->method('fetchRow')->willReturn(['rating_summary' => '90', 'reviews_count' => '17']);
        $rating = $this->provider->getRating(5, 1);
        $this->assertNotNull($rating);
        $this->assertSame('4.5', $rating['ratingValue']);
        $this->assertSame('17', $rating['reviewCount']);
        $this->assertSame('5', $rating['bestRating']);
        $this->assertSame('1', $rating['worstRating']);
    }

    public function testRoundsRatingToOneDecimal(): void
    {
        // 73/20 = 3.65 → rounded to 3.7
        $this->connection->method('fetchRow')->willReturn(['rating_summary' => '73', 'reviews_count' => '4']);
        $rating = $this->provider->getRating(5, 1);
        $this->assertNotNull($rating);
        $this->assertSame('3.7', $rating['ratingValue']);
    }
}
