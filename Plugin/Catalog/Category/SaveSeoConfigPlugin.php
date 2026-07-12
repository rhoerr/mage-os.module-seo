<?php

declare(strict_types=1);

namespace MageOS\Seo\Plugin\Catalog\Category;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use MageOS\Seo\Model\Category\ConfigRepository;
use Psr\Log\LoggerInterface;

/**
 * Persists the SEO tab data from the category edit form after the category is saved.
 *
 * Registered in etc/adminhtml/di.xml only: the plugin reads admin form POST data, which
 * must never influence REST/GraphQL/import/cron saves.
 */
class SaveSeoConfigPlugin
{
    /**
     * @param RequestInterface $request
     * @param ConfigRepository $configRepository
     * @param ManagerInterface $messageManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly ConfigRepository $configRepository,
        private readonly ManagerInterface $messageManager,
        private readonly LoggerInterface  $logger
    ) {
    }

    /**
     * After the category is saved, persist any SEO config submitted via the SEO tab.
     *
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $subject
     * @param \Magento\Catalog\Api\Data\CategoryInterface $result
     * @return \Magento\Catalog\Api\Data\CategoryInterface
     */
    public function afterSave(
        CategoryRepositoryInterface $subject,
        CategoryInterface           $result
    ): CategoryInterface {
        $categoryId = (int) $result->getId();
        if ($categoryId <= 0) {
            return $result;
        }

        /** @var \Magento\Framework\App\Request\Http $postRequest */
        $postRequest = $this->request;
        $postData = $postRequest->getPostValue();

        // Only proceed if the SEO tab fields were part of the submitted data
        if (!isset($postData['mageos_seo_schema_template']) &&
            !isset($postData['mageos_seo_enabled_fields']) &&
            !isset($postData['mageos_seo_robots_meta']) &&
            !isset($postData['mageos_seo_override_fields']) &&
            !isset($postData['mageos_seo_item_list_enabled'])) {
            return $result;
        }

        $data = [];

        if (isset($postData['mageos_seo_schema_template'])) {
            $data['schema_template'] = (string) $postData['mageos_seo_schema_template'];
        }

        if (isset($postData['mageos_seo_enabled_fields'])) {
            $fields = $postData['mageos_seo_enabled_fields'];
            $data['enabled_fields'] = \is_array($fields) ? $fields : [];
        }

        if (isset($postData['mageos_seo_item_list_enabled'])) {
            $val = $postData['mageos_seo_item_list_enabled'];
            $data['item_list_enabled'] = ($val === '') ? null : (int) $val;
        }

        if (isset($postData['mageos_seo_robots_meta'])) {
            $data['robots_meta'] = (string) $postData['mageos_seo_robots_meta'] ?: null;
        }

        if (isset($postData['mageos_seo_override_fields'])) {
            $raw = (string) $postData['mageos_seo_override_fields'];
            if ($raw === '') {
                $data['override_fields'] = [];
            } else {
                $decoded = json_decode($raw, true);
                if (\is_array($decoded)) {
                    $data['override_fields'] = $decoded;
                } else {
                    // Invalid JSON: keep the stored value rather than silently wiping it.
                    $this->messageManager->addErrorMessage(
                        (string) __('SEO override fields were not saved: the value is not valid JSON.')
                    );
                }
            }
        }

        if (!empty($data)) {
            // Core admin category forms submit the selected store view as "store_id"
            // (the edit page URL carries "store"); the adminhtml current store is
            // always store 0, so reading the store manager here would silently pin
            // every override to the default scope.
            $storeId = max(0, (int) $this->request->getParam(
                'store_id',
                $this->request->getParam('store', 0)
            ));
            try {
                $this->configRepository->save($categoryId, $data, $storeId);
            } catch (\Throwable $e) {
                // The category itself is already committed; a SEO-table failure must
                // not make the whole save look failed.
                $this->logger->error(
                    'MageOS_Seo: could not save category SEO config: ' . $e->getMessage(),
                    ['exception' => $e, 'category_id' => $categoryId, 'store_id' => $storeId]
                );
                $this->messageManager->addErrorMessage(
                    (string) __('The category was saved, but its SEO settings could not be saved.')
                );
            }
        }

        return $result;
    }
}
