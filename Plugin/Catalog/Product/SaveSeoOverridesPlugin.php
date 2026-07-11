<?php

declare(strict_types=1);

namespace MageOS\Seo\Plugin\Catalog\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use MageOS\Seo\Model\Category\ProductOverrideRepository;
use Psr\Log\LoggerInterface;

/**
 * Persists the Advanced SEO tab data from the product edit form after the product is saved.
 *
 * Registered in etc/adminhtml/di.xml only: the plugin reads admin form POST data, which
 * must never influence REST/GraphQL/import/cron saves.
 */
class SaveSeoOverridesPlugin
{
    /**
     * @param RequestInterface $request
     * @param ProductOverrideRepository $productOverrideRepository
     * @param ManagerInterface $messageManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly RequestInterface          $request,
        private readonly ProductOverrideRepository $productOverrideRepository,
        private readonly ManagerInterface          $messageManager,
        private readonly LoggerInterface           $logger
    ) {
    }

    /**
     * After the product is saved, persist any SEO overrides from the Advanced SEO tab.
     *
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $subject
     * @param \Magento\Catalog\Api\Data\ProductInterface $result
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    public function afterSave(
        ProductRepositoryInterface $subject,
        ProductInterface           $result
    ): ProductInterface {
        $productId = (int) $result->getId();
        if ($productId <= 0) {
            return $result;
        }

        /** @var \Magento\Framework\App\Request\Http $postRequest */
        $postRequest = $this->request;
        $postData = $postRequest->getPostValue();

        if (!isset($postData['mageos_seo_override_fields']) && !isset($postData['mageos_seo_robots_meta'])) {
            return $result;
        }

        // Core admin catalog passes the selected store view as the "store" request
        // parameter; the adminhtml current store is always store 0, so reading the
        // store manager here would silently pin every override to the default scope.
        $storeId = max(0, (int) $this->request->getParam('store', 0));
        $data    = [];

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

        if (isset($postData['mageos_seo_robots_meta'])) {
            $data['robots_meta'] = (string) $postData['mageos_seo_robots_meta'] ?: null;
        }

        if (!empty($data)) {
            try {
                $this->productOverrideRepository->save($productId, $storeId, $data);
            } catch (\Throwable $e) {
                // The product itself is already committed; a SEO-table failure must not
                // make the whole save look failed.
                $this->logger->error(
                    'MageOS_Seo: could not save product SEO overrides: ' . $e->getMessage(),
                    ['exception' => $e, 'product_id' => $productId, 'store_id' => $storeId]
                );
                $this->messageManager->addErrorMessage(
                    (string) __('The product was saved, but its SEO overrides could not be saved.')
                );
            }
        }

        return $result;
    }
}
