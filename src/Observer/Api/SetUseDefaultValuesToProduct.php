<?php declare(strict_types=1);

namespace ReachDigital\ImprovedApi\Observer\Api;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManager;

class SetUseDefaultValuesToProduct implements ObserverInterface
{
    protected StoreManager $storeManager;

    public function __construct(StoreManager $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    public function execute(Observer $observer): void
    {
        $entity = $observer->getEvent()->getEntity();
        $extensionAttributes = $entity->getExtensionAttributes();
        $storeId = $this->storeManager->getStore()->getId();

        $use_default_attributes = $extensionAttributes->getUseDefault();
        if (!empty($use_default_attributes) && is_array($use_default_attributes)) {
            foreach ($extensionAttributes->getUseDefault() as $attribute_code) {
                $entity->setData($attribute_code, null);
            }
        }

        if (!empty($use_default_attributes) && (int)$storeId === 0) {
            throw new \Exception(
                'Can\'t set default values if there is no store view specified'
            );
        }
    }
}
