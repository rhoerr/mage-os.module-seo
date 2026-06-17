<?php

declare(strict_types=1);

namespace MageOS\Seo\Api;

use MageOS\Seo\Api\Data\FaqInterface;

interface FaqRepositoryInterface
{
    /**
     * Load a FAQ entry by ID.
     *
     * @param int $entityId
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return \MageOS\Seo\Api\Data\FaqInterface
     */
    public function getById(int $entityId): FaqInterface;

    /**
     * Persist a FAQ entry.
     *
     * @param \MageOS\Seo\Api\Data\FaqInterface $faq
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @return \MageOS\Seo\Api\Data\FaqInterface
     */
    public function save(FaqInterface $faq): FaqInterface;

    /**
     * Delete a FAQ entry.
     *
     * @param \MageOS\Seo\Api\Data\FaqInterface $faq
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @return void
     */
    public function delete(FaqInterface $faq): void;

    /**
     * Delete a FAQ entry by ID.
     *
     * @param int $entityId
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @return void
     */
    public function deleteById(int $entityId): void;
}
