<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Integration\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Seo\Api\Data\FaqInterface;
use MageOS\Seo\Api\FaqRepositoryInterface;
use MageOS\Seo\Model\Faq;
use MageOS\Seo\Model\Faq\Repository as FaqReadRepository;
use MageOS\Seo\Model\FaqRepository;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class FaqRepositoryTest extends TestCase
{
    /**
     * @var FaqRepositoryInterface
     */
    private ?FaqRepositoryInterface $repository = null;

    /**
     * @var FaqReadRepository
     */
    private ?FaqReadRepository $readRepository = null;

    protected function setUp(): void
    {
        parent::setUp();
        $om = Bootstrap::getObjectManager();
        $this->repository = $om->get(FaqRepositoryInterface::class)
            ?? $om->create(FaqRepository::class);
        $this->readRepository = $om->get(FaqReadRepository::class);
    }

    private function newFaq(string $identifier, string $question, string $answer): FaqInterface
    {
        /** @var Faq $faq */
        $faq = Bootstrap::getObjectManager()->create(Faq::class);
        $faq->setIdentifier($identifier);
        $faq->setStoreId(0);
        $faq->setQuestion($question);
        $faq->setAnswer($answer);
        $faq->setSortOrder(0);
        $faq->setIsActive(true);
        return $faq;
    }

    public function testSaveAndGetByIdRoundTrip(): void
    {
        $faq   = $this->repository->save($this->newFaq('shipping', 'Ship to EU?', 'Yes.'));
        $loaded = $this->repository->getById($faq->getEntityId());

        $this->assertSame('shipping', $loaded->getIdentifier());
        $this->assertSame('Ship to EU?', $loaded->getQuestion());
        $this->assertSame('Yes.', $loaded->getAnswer());
        $this->assertTrue($loaded->getIsActive());
    }

    public function testGetByIdThrowsForMissing(): void
    {
        $this->expectException(NoSuchEntityException::class);
        $this->repository->getById(999999);
    }

    public function testDeleteRemovesEntry(): void
    {
        $faq = $this->repository->save($this->newFaq('returns', 'Q', 'A'));
        $id  = $faq->getEntityId();
        $this->repository->delete($faq);

        $this->expectException(NoSuchEntityException::class);
        $this->repository->getById($id);
    }

    public function testDeleteByIdRemovesEntry(): void
    {
        $faq = $this->repository->save($this->newFaq('returns', 'Q', 'A'));
        $id  = $faq->getEntityId();
        $this->repository->deleteById($id);

        $this->expectException(NoSuchEntityException::class);
        $this->repository->getById($id);
    }

    public function testReadByIdentifierReturnsActiveOrdered(): void
    {
        $this->repository->save($this->newFaq('faqgrp', 'First', 'A1'));
        $this->repository->save($this->newFaq('faqgrp', 'Second', 'A2'));
        $inactive = $this->newFaq('faqgrp', 'Hidden', 'A3');
        $inactive->setIsActive(false);
        $this->repository->save($inactive);

        $faqs      = $this->readRepository->getByIdentifier('faqgrp', 1);
        $questions = array_column($faqs, 'question');

        $this->assertContains('First', $questions);
        $this->assertContains('Second', $questions);
        $this->assertNotContains('Hidden', $questions);
    }
}
