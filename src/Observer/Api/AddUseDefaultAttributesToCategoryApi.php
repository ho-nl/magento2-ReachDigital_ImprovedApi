<?php declare(strict_types=1);

namespace ReachDigital\ImprovedApi\Observer\Api;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddUseDefaultAttributesToCategoryApi implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $entity = $observer->getEvent()->getEntity();
        $extensionAttributes = $entity->getExtensionAttributes();

        $attr = $entity->getData('use_default') ?? [];
        $extensionAttributes->setUseDefault($attr);
        $entity->setExtensionAttributes($extensionAttributes);
    }
}