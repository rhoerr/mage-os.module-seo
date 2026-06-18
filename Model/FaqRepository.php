<?php

declare(strict_types=1);

namespace MageOS\Seo\Model;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use MageOS\Seo\Api\Data\FaqInterface;
use MageOS\Seo\Api\FaqRepositoryInterface;
use MageOS\Seo\Model\ResourceModel\Faq as FaqResource;

class FaqRepository implements FaqRepositoryInterface
{
    /**
     * @param FaqFactory $faqFactory
     * @param FaqResource $resource
     */
    public function __construct(
        private readonly FaqFactory  $faqFactory,
        private readonly FaqResource $resource
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getById(int $entityId): FaqInterface
    {
        $faq = $this->faqFactory->create();
        $this->resource->load($faq, $entityId);
        if ($faq->getEntityId() === 0) {
            throw new NoSuchEntityException(__('FAQ with id "%1" does not exist.', $entityId));
        }
        return $faq;
    }

    /**
     * @inheritdoc
     */
    public function save(FaqInterface $faq): FaqInterface
    {
        if (!$faq instanceof AbstractModel) {
            throw new CouldNotSaveException(__('FAQ model must extend AbstractModel.'));
        }
        try {
            $this->resource->save($faq);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the FAQ: %1', $e->getMessage()), $e);
        }
        return $faq;
    }

    /**
     * @inheritdoc
     */
    public function delete(FaqInterface $faq): void
    {
        if (!$faq instanceof AbstractModel) {
            throw new CouldNotDeleteException(__('FAQ model must extend AbstractModel.'));
        }
        try {
            $this->resource->delete($faq);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the FAQ: %1', $e->getMessage()), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $entityId): void
    {
        $this->delete($this->getById($entityId));
    }
}
