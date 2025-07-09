<?php
/**
 * Copyright © Reach Digital (http://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */

namespace ReachDigital\ImprovedApi\Api;

/**
 * @api
 */
interface ProductRepositoryInterface
{
    /**
     * Update product.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface        $product
     * @param \Magento\Catalog\Api\Data\ProductInterface|null   $productDefault
     *
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     *
     * @return bool
     */
    public function update(
        \Magento\Catalog\Api\Data\ProductInterface $product,
        ?\Magento\Catalog\Api\Data\ProductInterface $productDefault = null,
    );
}
