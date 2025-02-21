<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */

namespace ReachDigital\ImprovedApi\Model;

use Magento\Backend\App\ConfigInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Store\Model\StoreManagerInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;

class ProductRepository implements \ReachDigital\ImprovedApi\Api\ProductRepositoryInterface
{
    public const XML_PATH_QUICK_API_ENABLED = 'improved_api/general/enabled';

    public const NON_QUICK_ATTRIBUTES = [
        'media_gallery',
        'options',
        'tier_price',
        'ignore_links_flag',
        'attribute_set_id',
    ];

    /** @var ProductRepositoryInterface $productRepository */
    private $productRepository;

    /**
     * @var ProductAction
     */
    private $productAction;

    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /** @var ConfigInterface  */
    private $config;

    /**  @param ProductRepositoryInterface $productRepository */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductAction $productAction,
        ProductResource $productResource,
        StoreManagerInterface $storeManager,
        StockRegistryInterface $stockRegistry,
        ConfigInterface $config,
    ) {
        $this->productRepository = $productRepository;
        $this->productAction = $productAction;
        $this->productResource = $productResource;
        $this->storeManager = $storeManager;
        $this->stockRegistry = $stockRegistry;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function update(ProductInterface $product, ProductInterface $productDefault = null)
    {
        if (
            $this->config->isSetFlag(self::XML_PATH_QUICK_API_ENABLED) &&
            $this->canUseQuickUpdate($product, $productDefault)
        ) {
            return $this->quickUpdate($product, $productDefault);
        }

        return $this->repositoryUpdate($product, $productDefault);
    }

    /**
     * Currently only stock and normal attributes are implemented
     * if other attributes like images or links are present quick update is not allowed.
     */
    public function canUseQuickUpdate(ProductInterface $product, ProductInterface $productDefault = null): bool
    {
        if ($productDefault && !$this->canUseQuickUpdate($productDefault)) {
            return false;
        }
        //        Only stock_item is allowed as extension attribute
        $extensionAttributes = $product->getExtensionAttributes()->__toArray();
        if (
            count($extensionAttributes) > 1 ||
            (count($extensionAttributes) === 1 && !isset($extensionAttributes['stock_item']))
        ) {
            return false;
        }

        return !array_intersect(array_keys($product->getData()), self::NON_QUICK_ATTRIBUTES);
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
        $attributes = $this->getAttributes($product, $productDefault);
        $stockAttributes = $this->getStockAttributes($product, $productDefault);
        if (!isset($attributes['sku'])) {
            return false;
        }

        $entityId = $this->productResource->getIdBySku($attributes['sku']);
        $storeId = $this->storeManager->getStore()->getId();
        $this->productAction->updateAttributes([$entityId], $attributes, $storeId);

        if (!empty($stockAttributes)) {
            $stockItem = $this->stockRegistry->getStockItem($entityId);
            foreach ($stockAttributes as $stockAttribute => $value) {
                $stockItem->setData($stockAttribute, $value);
            }
            $this->stockRegistry->updateStockItemBySku($attributes['sku'], $stockItem);
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
        if ($productDefault) {
            foreach ($productDefault->getData() as $attribute => $value) {
                if (in_array($attribute, ['extension_attributes', 'created_at', 'entity_id'])) {
                    continue;
                }
                $attributes[$attribute] = $value;
            }
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
