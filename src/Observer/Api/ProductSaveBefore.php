<?php declare(strict_types=1);

namespace ReachDigital\ImprovedApi\Observer\Api;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProductSaveBefore implements ObserverInterface
{

    public function execute(Observer $observer)
    {
        $entity = $observer->getEvent()->getEntity();
        $extensionAttributes = $entity->getExtensionAttributes();

        // @TODO check if use default is available for attribute in store view
        $use_default_attributes = $extensionAttributes->getUseDefault();
        if (!empty($use_default_attributes) && is_array($use_default_attributes)) {
            foreach ($extensionAttributes->getUseDefault() as $index => $attribute_code) {
                $entity->setData($attribute_code, null);
            }
        }
    }
}