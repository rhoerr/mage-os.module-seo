<?php

declare(strict_types=1);

namespace MageOS\Seo\Block\Adminhtml\Faq\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @inheritdoc
     *
     * @return array<string, mixed>
     */
    public function getButtonData(): array
    {
        if ($this->getFaqId() === null) {
            return [];
        }

        $url = $this->getUrl('*/*/delete', ['entity_id' => $this->getFaqId()]);

        return [
            'label'      => (string) __('Delete'),
            'class'      => 'delete',
            'on_click'   => \sprintf(
                "deleteConfirm('%s', '%s')",
                (string) __('Are you sure you want to delete this FAQ?'),
                $url
            ),
            'sort_order' => 20,
        ];
    }
}
