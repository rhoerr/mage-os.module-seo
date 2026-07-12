<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\Feed;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem\Directory\WriteFactory;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use MageOS\Seo\Model\Config;
use MageOS\Seo\Model\Feed\FeedStorage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FeedStorageTest extends TestCase
{
    /**
     * @var Filesystem&MockObject
     */
    private Filesystem&MockObject $filesystem;

    /**
     * @var WriteFactory&MockObject
     */
    private WriteFactory&MockObject $writeFactory;

    /**
     * @var ReadFactory&MockObject
     */
    private ReadFactory&MockObject $readFactory;

    /**
     * @var Config&MockObject
     */
    private Config&MockObject $config;

    protected function setUp(): void
    {
        $this->filesystem   = $this->createMock(Filesystem::class);
        $this->writeFactory = $this->createMock(WriteFactory::class);
        $this->readFactory  = $this->createMock(ReadFactory::class);
        $this->config       = $this->createMock(Config::class);
    }

    private function storage(): FeedStorage
    {
        return new FeedStorage($this->filesystem, $this->writeFactory, $this->readFactory, $this->config);
    }

    public function testWriteUsesVarDirWithPrefixByDefault(): void
    {
        $this->config->method('getFeedStorageDir')->willReturn('');
        $writeDir = $this->createMock(WriteInterface::class);
        $this->filesystem->method('getDirectoryWrite')->with(DirectoryList::VAR_DIR)->willReturn($writeDir);

        $writeDir->expects($this->once())->method('writeFile')
            ->with('mageos_seo/store_1/llms.txt', 'the-body');

        $this->storage()->write('llms.txt', 1, 'the-body');
    }

    public function testWriteUsesCustomDirectoryWithoutThePrefix(): void
    {
        $this->config->method('getFeedStorageDir')->willReturn('/shared/feeds');
        $writeDir = $this->createMock(WriteInterface::class);
        $this->writeFactory->method('create')->with('/shared/feeds')->willReturn($writeDir);
        $this->filesystem->expects($this->never())->method('getDirectoryWrite');

        $writeDir->expects($this->once())->method('writeFile')
            ->with('store_1/llms.txt', 'the-body');

        $this->storage()->write('llms.txt', 1, 'the-body');
    }

    public function testReadReturnsContentWhenFileExists(): void
    {
        $this->config->method('getFeedStorageDir')->willReturn('');
        $readDir = $this->createMock(ReadInterface::class);
        $this->filesystem->method('getDirectoryRead')->with(DirectoryList::VAR_DIR)->willReturn($readDir);
        $readDir->method('isFile')->with('mageos_seo/store_1/llms.txt')->willReturn(true);
        $readDir->method('readFile')->with('mageos_seo/store_1/llms.txt')->willReturn('the-body');

        $this->assertSame('the-body', $this->storage()->read('llms.txt', 1));
    }

    public function testReadReturnsNullWhenFileMissing(): void
    {
        $this->config->method('getFeedStorageDir')->willReturn('');
        $readDir = $this->createMock(ReadInterface::class);
        $this->filesystem->method('getDirectoryRead')->willReturn($readDir);
        $readDir->method('isFile')->willReturn(false);

        $this->assertNull($this->storage()->read('llms.txt', 1));
    }

    public function testReadReturnsNullOnFilesystemException(): void
    {
        $this->config->method('getFeedStorageDir')->willReturn('');
        $readDir = $this->createMock(ReadInterface::class);
        $this->filesystem->method('getDirectoryRead')->willReturn($readDir);
        $readDir->method('isFile')->willThrowException(new \RuntimeException('io'));

        $this->assertNull($this->storage()->read('llms.txt', 1));
    }

    public function testDeleteForStoreScopesTheGlobToOneStore(): void
    {
        $this->config->method('getFeedStorageDir')->willReturn('');
        $writeDir = $this->createMock(WriteInterface::class);
        $this->filesystem->method('getDirectoryWrite')->willReturn($writeDir);

        $writeDir->method('search')->with('mageos_seo/store_2/hreflang-sitemap*.xml')
            ->willReturn(['mageos_seo/store_2/hreflang-sitemap.xml']);
        $writeDir->expects($this->once())->method('delete')
            ->with('mageos_seo/store_2/hreflang-sitemap.xml');

        $this->storage()->deleteForStore('hreflang-sitemap*.xml', 2);
    }

    public function testDeleteForAllStoresUsesAWildcardStoreSegment(): void
    {
        $this->config->method('getFeedStorageDir')->willReturn('');
        $writeDir = $this->createMock(WriteInterface::class);
        $this->filesystem->method('getDirectoryWrite')->willReturn($writeDir);

        $writeDir->method('search')->with('mageos_seo/*/llms.txt')
            ->willReturn(['mageos_seo/store_1/llms.txt', 'mageos_seo/store_2/llms.txt']);
        $writeDir->expects($this->exactly(2))->method('delete');

        $this->storage()->deleteForAllStores('llms.txt');
    }
}
