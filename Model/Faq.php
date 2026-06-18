<?php

declare(strict_types=1);

namespace MageOS\Seo\Model;

use Magento\Framework\Model\AbstractModel;
use MageOS\Seo\Api\Data\FaqInterface;
use MageOS\Seo\Model\ResourceModel\Faq as FaqResource;

class Faq extends AbstractModel implements FaqInterface
{
    /**
     * Initialize resource model.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(FaqResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getEntityId(): int
    {
        return (int) $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier(): string
    {
        return (string) $this->getData(self::IDENTIFIER);
    }

    /**
     * @inheritdoc
     */
    public function setIdentifier(string $identifier): FaqInterface
    {
        return $this->setData(self::IDENTIFIER, $identifier);
    }

    /**
     * @inheritdoc
     */
    public function getStoreId(): int
    {
        return (int) $this->getData(self::STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setStoreId(int $storeId): FaqInterface
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * @inheritdoc
     */
    public function getQuestion(): string
    {
        return (string) $this->getData(self::QUESTION);
    }

    /**
     * @inheritdoc
     */
    public function setQuestion(string $question): FaqInterface
    {
        return $this->setData(self::QUESTION, $question);
    }

    /**
     * @inheritdoc
     */
    public function getAnswer(): string
    {
        return (string) $this->getData(self::ANSWER);
    }

    /**
     * @inheritdoc
     */
    public function setAnswer(string $answer): FaqInterface
    {
        return $this->setData(self::ANSWER, $answer);
    }

    /**
     * @inheritdoc
     */
    public function getSortOrder(): int
    {
        return (int) $this->getData(self::SORT_ORDER);
    }

    /**
     * @inheritdoc
     */
    public function setSortOrder(int $sortOrder): FaqInterface
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    /**
     * @inheritdoc
     */
    public function getIsActive(): bool
    {
        return (bool) $this->getData(self::IS_ACTIVE);
    }

    /**
     * @inheritdoc
     */
    public function setIsActive(bool $isActive): FaqInterface
    {
        return $this->setData(self::IS_ACTIVE, $isActive ? 1 : 0);
    }
}
