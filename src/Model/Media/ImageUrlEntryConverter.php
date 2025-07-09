<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */

namespace ReachDigital\ImprovedApi\Model\Media;

use Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface;
use Magento\Catalog\Model\Product\Attribute\Backend\Media\ImageEntryConverter;
use Magento\Framework\Api\Data\ImageContentInterface;
use ReachDigital\ImprovedApi\Api\UrlContentInterface;

class ImageUrlEntryConverter extends ImageEntryConverter
{
    const MEDIA_TYPE_CODE = 'url';

    public function getMediaEntryType(): string
    {
        return self::MEDIA_TYPE_CODE;
    }

    public function convertFrom(ProductAttributeMediaGalleryEntryInterface $entry)
    {
        $entryArray = parent::convertFrom($entry);

        $entryArray['media_type'] = parent::getMediaEntryType();
        $entryArray['content'] = $this->convertFromImageUrl($entry->getExtensionAttributes()->getUrlContent());

        return $entryArray;
    }

    /**
     * @param UrlContentInterface|null $urlContent
     * @return array|array[]
     */
    public function convertFromImageUrl(?UrlContentInterface $urlContent = null): array
    {
        if ($urlContent->getUrl() === null) {
            return [];
        }

        $fileContent = file_get_contents($urlContent->getUrl());
        $mimeType = $urlContent->getType() ?? $this->getMimeTypeFromHeaders($http_response_header); //  $http_response_header is a global var
        $imageName = $urlContent->getName();
        if (!$imageName) {
            $imageName = explode('/', $urlContent->getUrl()) ?? [];
            $imageName = end($imageName);
        }

        return [
            'data' => [
                ImageContentInterface::BASE64_ENCODED_DATA => base64_encode($fileContent),
                ImageContentInterface::TYPE => $mimeType,
                ImageContentInterface::NAME => $imageName,
            ],
        ];
    }

    /**
     * @throws \Exception
     */
    public function getMimeTypeFromHeaders(array $headers)
    {
        foreach ($headers as $header) {
            if (substr($header, 0, strlen('Content-Type: image')) === 'Content-Type: image') {
                return substr($header, strlen('Content-Type: '));
            }
        }
        throw new \Exception('No image mime type found for the product, provide your own');
    }
}
