<?php
/**
 * Copyright © Reach Digital (https://www.reachdigital.io/)
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace ReachDigital\ImprovedApi\Plugin;

use Magento\AsynchronousOperations\Model\MassConsumer;
use Magento\Framework\Registry;

class FlagConsumerProcess
{
    const FLAG = 'isQueueConsumerArea';

    public function __construct(public Registry $registry)
    {
    }

    public function beforeProcess(MassConsumer $subject, $maxNumberOfMessages): array
    {
        $this->registry->register(self::FLAG, true, true);

        return [$maxNumberOfMessages];
    }
}
