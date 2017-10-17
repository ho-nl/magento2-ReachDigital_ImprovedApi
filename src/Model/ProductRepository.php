<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */

namespace ReachDigital\ImprovedApi\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;

class ProductRepository implements \ReachDigital\ImprovedApi\Api\ProductRepositoryInterface
{
    /** @var ProductRepositoryInterface $productRepository */
    private $productRepository;

    /**
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function update(ProductInterface $productUpdate, ProductInterface $productDefault = null)
    {
        $existingProduct = $this->productRepository->get($productUpdate->getSku());

        /** @noinspection PhpParamsInspection */
        $dataChanged = $this->applyData($existingProduct, $productUpdate, $productDefault);
        if ($dataChanged) {
            $this->productRepository->save($existingProduct);
        }

        return true;
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
                if (! $existingProduct->getData($attribute)) {
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
