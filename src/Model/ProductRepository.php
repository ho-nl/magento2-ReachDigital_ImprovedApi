<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */

namespace ReachDigital\ImprovedApi\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Store\Model\StoreManagerInterface;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;

class ProductRepository implements \ReachDigital\ImprovedApi\Api\ProductRepositoryInterface
{
    /** @var ProductRepositoryInterface $productRepository */
    private $productRepository;

    private ProductAction $productAction;

    private ProductResource $productResource;

    private StoreManagerInterface $storeManager;

    private StockItemRepositoryInterface $stockItemRepository;

    /**
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductAction $productAction,
        ProductResource $productResource,
        StoreManagerInterface $storeManager,
        StockItemRepositoryInterface $stockItemRepository,
    ) {
        $this->productRepository = $productRepository;
        $this->productAction = $productAction;
        $this->productResource = $productResource;
        $this->storeManager = $storeManager;
        $this->stockItemRepository = $stockItemRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function update(ProductInterface $product, ProductInterface $productDefault = null)
    {
        $quick = true;
        if ($quick) {
            return $this->quickUpdate($product, $productDefault);
        }
        return $this->repositoryUpdate($product, $productDefault);
    }

    public function repositoryUpdate(ProductInterface $product, ProductInterface $productDefault = null)
    {
        $existingProduct = $this->productRepository->get($product->getSku());

        /** @noinspection PhpParamsInspection */
        $dataChanged = $this->applyData($existingProduct, $product, $productDefault);
        if ($dataChanged) {
            $this->productRepository->save($existingProduct);
        }

        return true;
    }

    public function quickUpdate(ProductInterface $product, ProductInterface $productDefault = null)
    {
        $attribues = $this->getAttributes($product, $productDefault);
        $stockAttributes = $this->getStockAttributes($product, $productDefault);
        if (!isset($attribues['sku'])) {
            return false;
        }

        $entityId = $this->productResource->getIdBySku($attribues['sku']);
        $storeId = $this->storeManager->getStore()->getId();
        $this->productAction->updateAttributes([$entityId], $attribues, $storeId);

        if (!empty($stockAttributes)) {
            $stockItem = $this->stockItemRepository->get($entityId);
            foreach ($stockAttributes as $stockAttribute => $value) {
                $stockItem->setData($stockAttribute, $value);
            }
            $this->stockItemRepository->save($stockItem);
        }

        return true;
    }

    public function getStockAttributes(ProductInterface $product, ProductInterface $productDefault = null): array
    {
        $stockAttributes = [];
        if ($productDefault !== null) {
            $stockData =
                $productDefault
                    ->getExtensionAttributes()
                    ?->getStockItem()
                    ?->getData() ?? [];
            foreach ($stockData as $stockAttribute => $value) {
                $stockAttributes[$stockAttribute] = $value;
            }
        }
        $stockData =
            $product
                ->getExtensionAttributes()
                ?->getStockItem()
                ?->getData() ?? [];
        foreach ($stockData as $stockAttribute => $value) {
            $stockAttributes[$stockAttribute] = $value;
        }

        return $stockAttributes;
    }

    public function getAttributes(ProductInterface $product, ProductInterface $productDefault = null): array
    {
        $attributes = [];
        foreach ($productDefault->getData() as $attribute => $value) {
            if ($attribute === 'extension_attributes') {
                continue;
            }
            $attributes[$attribute] = $value;
        }
        foreach ($product->getData() as $attribute => $value) {
            if ($attribute === 'extension_attributes') {
                continue;
            }
            $attributes[$attribute] = $value;
        }
        return $attributes;
    }

    /**
     * Updates the $existingProduct with the data from $productUpdate and $productDefault.
     *
     * @param Product       $existingProduct
     * @param Product       $productUpdate
     * @param Product|null  $productDefault
     *
     * @return bool         True if data has changed, false if data hasn't changed.
     */
    private function applyData(Product $existingProduct, Product $productUpdate, Product $productDefault = null)
    {
        $dataChanged = false;

        if ($productDefault !== null) {
            foreach ($productDefault->getData() as $attribute => $value) {
                if (!$existingProduct->getData($attribute)) {
                    $existingProduct->setData($attribute, $value);

                    $dataChanged = true;
                }
            }
        }

        foreach ($productUpdate->getData() as $attribute => $value) {
            if ($existingProduct->getData($attribute) != $value) {
                $existingProduct->setData($attribute, $value);

                $dataChanged = true;
            }
        }

        if ($dataChanged) {
            $this->setMediaAttributeType($existingProduct);
        }

        return $dataChanged;
    }

    /**
     * Set media type [image, small_image, thumbnail, swatch_image] in product media gallery so it will be re-added.
     * @see \Magento\Catalog\Model\ProductRepository::processMediaGallery()
     *
     * @param Product $existingProduct
     *
     * @return void
     */
    private function setMediaAttributeType(Product $existingProduct)
    {
        if ($images = $existingProduct->getMediaGallery('images')) {
            $mediaAttributes = array_keys($existingProduct->getMediaAttributes());

            foreach ($images as &$image) {
                foreach ($mediaAttributes as $mediaAttribute) {
                    $productImageFile = $existingProduct->getData($mediaAttribute);

                    if ($image['file'] === $productImageFile) {
                        $image['types'][] = $mediaAttribute;
                    }
                }
            }

            $existingProduct->setData('media_gallery', ['images' => $images]);
        }
    }
}
