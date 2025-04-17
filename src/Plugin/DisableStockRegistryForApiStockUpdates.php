<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace ReachDigital\ImprovedApi\Plugin;

use Magento\CatalogInventory\Model\StockRegistryStorage;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;

class DisableStockRegistryForApiStockUpdates
{
    const XML_PATH_DISABLE_STOCK_REGISTRY = 'improved_api/stock_registry/disabled';

    public function __construct(public ScopeConfigInterface $scopeConfig, public Registry $registry)
    {
    }

    public function aroundGetStockItem(StockRegistryStorage $subject, callable $proceed, int $productId, int $scopeId)
    {
        if (
            $this->registry->registry(FlagConsumerProcess::FLAG) &&
            $this->scopeConfig->isSetFlag(self::XML_PATH_DISABLE_STOCK_REGISTRY)
        ) {
            return null;
        }

        return $proceed($productId, $scopeId);
    }
}
