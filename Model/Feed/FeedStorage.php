<?php

declare(strict_types=1);

namespace MageOS\Seo\Model\Feed;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem\Directory\WriteFactory;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use MageOS\Seo\Model\Config;

/**
 * File storage for pre-generated SEO feeds (llms.txt, llms-full.txt, llms.jsonl,
 * hreflang sitemap files), one directory per store view.
 *
 * Defaults to var/mageos_seo/store_<id>/; a custom absolute directory can be
 * configured (mageos_seo_general/feeds/storage_dir) so multi-server deployments
 * can point web servers and the cron/consumer host at a shared mount — var/ is
 * host-local on scaled setups.
 */
class FeedStorage
{
    private const DEFAULT_BASE_DIR = 'mageos_seo';

    /**
     * @param Filesystem $filesystem
     * @param WriteFactory $writeFactory
     * @param ReadFactory $readFactory
     * @param Config $seoConfig
     */
    public function __construct(
        private readonly Filesystem   $filesystem,
        private readonly WriteFactory $writeFactory,
        private readonly ReadFactory  $readFactory,
        private readonly Config       $seoConfig
    ) {
    }

    /**
     * Persist a feed file for a store.
     *
     * @param string $fileName
     * @param int $storeId
     * @param string $content
     * @throws \Magento\Framework\Exception\FileSystemException
     * @return void
     */
    public function write(string $fileName, int $storeId, string $content): void
    {
        $this->getWrite()->writeFile($this->path($fileName, $storeId), $content);
    }

    /**
     * Read a feed file for a store, or null when it has not been generated.
     *
     * @param string $fileName
     * @param int $storeId
     * @return string|null
     */
    public function read(string $fileName, int $storeId): ?string
    {
        try {
            $dir  = $this->getRead();
            $path = $this->path($fileName, $storeId);
            if (!$dir->isFile($path)) {
                return null;
            }
            return $dir->readFile($path);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Delete matching feed files for one store.
     *
     * @param string $fileNamePattern Glob pattern, e.g. "hreflang-sitemap*.xml"
     * @param int $storeId
     * @return void
     */
    public function deleteForStore(string $fileNamePattern, int $storeId): void
    {
        $this->deleteByPattern('store_' . $storeId . '/' . $fileNamePattern);
    }

    /**
     * Delete matching feed files across every store directory.
     *
     * @param string $fileNamePattern Glob pattern, e.g. "llms.txt" or "hreflang-sitemap*.xml"
     * @return void
     */
    public function deleteForAllStores(string $fileNamePattern): void
    {
        $this->deleteByPattern('*/' . $fileNamePattern);
    }

    /**
     * Delete every file matching a storage-relative glob pattern (best effort).
     *
     * @param string $relativePattern
     * @return void
     */
    private function deleteByPattern(string $relativePattern): void
    {
        try {
            $dir = $this->getWrite();
            foreach ($dir->search($this->prefix() . $relativePattern) as $path) {
                $dir->delete($path);
            }
        } catch (\Exception) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch -- best-effort cleanup
        }
    }

    /**
     * Build the storage-relative path of a feed file.
     *
     * @param string $fileName
     * @param int $storeId
     * @return string
     */
    private function path(string $fileName, int $storeId): string
    {
        return $this->prefix() . 'store_' . $storeId . '/' . $fileName;
    }

    /**
     * Path prefix inside the storage root ('' for a custom absolute directory).
     *
     * @return string
     */
    private function prefix(): string
    {
        return $this->seoConfig->getFeedStorageDir() === '' ? self::DEFAULT_BASE_DIR . '/' : '';
    }

    /**
     * Writable handle on the storage root.
     *
     * @return WriteInterface
     */
    private function getWrite(): WriteInterface
    {
        $custom = $this->seoConfig->getFeedStorageDir();
        if ($custom !== '') {
            return $this->writeFactory->create($custom);
        }

        return $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
    }

    /**
     * Readable handle on the storage root.
     *
     * @return ReadInterface
     */
    private function getRead(): ReadInterface
    {
        $custom = $this->seoConfig->getFeedStorageDir();
        if ($custom !== '') {
            return $this->readFactory->create($custom);
        }

        return $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
    }
}
