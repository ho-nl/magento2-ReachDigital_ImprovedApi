<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */

namespace ReachDigital\ImprovedApi\Model\Media;

use Magento\Framework\Model\AbstractExtensibleModel;
use ReachDigital\ImprovedApi\Api\UrlContentInterface;

class UrlEntry extends AbstractExtensibleModel implements UrlContentInterface
{
    public function getUrl()
    {
        return $this->getData('url');
    }

    public function setUrl($imageUrl)
    {
        return $this->setData('url', $imageUrl);
    }

    public function getType()
    {
        return $this->getData('type');
    }

    public function setType($mimeType)
    {
        return $this->setData('type', $mimeType);
    }

    public function getName()
    {
        return $this->getData('name');
    }

    public function setName($name)
    {
        return $this->setData('name', $name);
    }
}
