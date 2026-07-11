<?php

declare(strict_types=1);

namespace MageOS\Seo\Controller\Adminhtml\Faq;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Seo\Api\FaqRepositoryInterface;
use MageOS\Seo\Model\Feed\FeedInvalidator;

class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_Seo::faq';

    /**
     * @param Context $context
     * @param FaqRepositoryInterface $faqRepository
     * @param FeedInvalidator $feedInvalidator
     */
    public function __construct(
        Context                                 $context,
        private readonly FaqRepositoryInterface $faqRepository,
        private readonly FeedInvalidator        $feedInvalidator
    ) {
        parent::__construct($context);
    }

    /**
     * Delete a FAQ entry.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $entityId       = (int) $this->getRequest()->getParam('entity_id');

        if ($entityId === 0) {
            $this->messageManager->addErrorMessage((string) __('No FAQ specified to delete.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $this->faqRepository->deleteById($entityId);
            // Cached pages purge via the model's identities; bridge providers may embed
            // FAQ content in llms.txt, so those feed files regenerate too.
            $this->feedInvalidator->invalidateLlms();
            $this->messageManager->addSuccessMessage((string) __('The FAQ entry has been deleted.'));
        } catch (NoSuchEntityException) {
            $this->messageManager->addErrorMessage((string) __('This FAQ no longer exists.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}
