<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace ReachDigital\ImprovedApi\Plugin;

use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\StockRegistry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;

class PreventUpdatingBackorderQty
{
    const XML_PATH_PREVENT_UPDATING_BACKORDER_QTY = 'improved_api/stock_updates/prevent_update_backorder_qty';

    public function __construct(
        public Registry $registry,
        public ProductFactory $productFactory,
        public ScopeConfigInterface $scopeConfig,
    ) {
    }

    public function beforeUpdateStockItemBySku(
        StockRegistry $subject,
        $productSku,
        StockItemInterface $stockItem,
    ): array {
        if (
            $this->registry->registry(FlagConsumerProcess::FLAG) &&
            $this->scopeConfig->isSetFlag(self::XML_PATH_PREVENT_UPDATING_BACKORDER_QTY)
        ) {
            $productId = $this->resolveProductId($productSku);
            $websiteId = $stockItem->getWebsiteId() ?: null;
            $origStockItem = $subject->getStockItem($productId, $websiteId);

            if ($stockItem->getQty() == 0 && $origStockItem->getQty() < 0) {
                $stockItem->setQty($origStockItem->getQty());
            }
        }

        return [$productSku, $stockItem];
    }

    public function resolveProductId($productSku)
    {
        $product = $this->productFactory->create();
        $productId = $product->getIdBySku($productSku);
        if (!$productId) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(
                __('The Product with the "%1" SKU doesn\'t exist.', $productSku),
            );
        }
        return $productId;
    }
}
