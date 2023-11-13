<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace ReachDigital\ImprovedApi\Plugin\Media;

use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface;
use Magento\Catalog\Api\ProductAttributeMediaGalleryManagementInterface;
use Magento\Framework\Api\Data\ImageContentInterfaceFactory;
use ReachDigital\ImprovedApi\Model\Media\ImageUrlEntryConverter;

class CreateMediaUrlContent
{
    /**
     * @var ImageUrlEntryConverter
     */
    private $imageUrlEntryConverter;
    /**
     * @var ImageContentInterfaceFactory
     */
    private $contentInterfaceFactory;

    public function __construct(
        ImageUrlEntryConverter $imageUrlEntryConverter,
        ImageContentInterfaceFactory $contentInterfaceFactory
    ) {
        $this->imageUrlEntryConverter = $imageUrlEntryConverter;
        $this->contentInterfaceFactory = $contentInterfaceFactory;
    }
    /**
     *
     */
    public function beforeCreate(
        ProductAttributeMediaGalleryManagementInterface $subject,
        $sku,
        ProductAttributeMediaGalleryEntryInterface $entry
    ) {
        if ($entry->getMediaType() == $this->imageUrlEntryConverter->getMediaEntryType()) {
            $urlEntry = $this->imageUrlEntryConverter->convertFrom($entry);
            $urlContent = $this->contentInterfaceFactory->create($urlEntry['content']);
            $entry->setContent($urlContent);
        }

        return [$sku, $entry];
    }
}
