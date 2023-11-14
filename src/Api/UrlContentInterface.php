<?php

/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */

namespace ReachDigital\ImprovedApi\Api;

use Magento\Framework\Api\ExtensibleDataInterface;

interface UrlContentInterface extends ExtensibleDataInterface
{
    /**
     * @return string
     */
    public function getUrl();

    public function setUrl($imageUrl);

    /**
     * @return string
     */
    public function getType();

    public function setType($mimeType);

    /**
     * @return string
     */
    public function getName();

    public function setName($name);
}
