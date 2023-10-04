<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace ReachDigital\ImprovedApi\Plugin;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryExtension;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\CategoryRepository\PopulateWithValues;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;

class HandleUseDefaultValuesForCategoryAttribute
{
    private CategoryResource $categoryResource;
    private PopulateWithValues $populateWithValues;

    public function __construct(CategoryResource $categoryResource, PopulateWithValues $populateWithValues)
    {
        $this->categoryResource = $categoryResource;
        $this->populateWithValues = $populateWithValues;
    }

    public function afterGet(CategoryRepositoryInterface $subject, CategoryInterface $result): CategoryInterface
    {
        /** @var CategoryExtension $extensionAttributes */
        $extensionAttributes = $result->getExtensionAttributes();

        $useDefault = $result->getData('use_default') ?? [];
        $extensionAttributes->setUseDefault($useDefault);
        $result->setExtensionAttributes($extensionAttributes);

        return $result;
    }

    /**
     * Possibility to pass an array with attribute codes to reset to default,
     * when updating a category via API.
     *
     * For example:
     * "extension_attributes": {
     *     "use_default": [
     *         "name",
     *         "display_mode"
     *     ]
     * }
     *
     * @throws \Exception
     */
    public function aroundSave(
        CategoryRepositoryInterface $subject,
        callable $proceed,
        CategoryInterface $category
    ): CategoryInterface {
        /** @var CategoryExtension $extensionAttributes */
        $extensionAttributes = $category->getExtensionAttributes();
        $useDefault = $extensionAttributes->getUseDefault();

        if (!empty($useDefault) && (int) $category->getStoreId() == 0) {
            throw new \Exception(
                'Can\'t set default values if there is no store view specified'
            );
        }

        $category = $proceed($category);

        // When use default is empty, no custom action is needed
        if (empty($useDefault) || !is_array($useDefault)) {
            return $category;
        }

        // Empty the values of given attributes
        $emptyAttributeValues = [];
        foreach ($useDefault as $attributeCode) {
            $emptyAttributeValues[$attributeCode] = null;
        }

        $this->populateWithValues->execute($category, $emptyAttributeValues);
        $this->categoryResource->save($category);

        return $category;
    }
}
