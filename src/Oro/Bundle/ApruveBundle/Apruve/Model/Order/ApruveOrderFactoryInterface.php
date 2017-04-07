<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Model\Order;

use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\OrderBundle\Entity\Order;

interface ApruveOrderFactoryInterface
{
    /**
     * @param Order $order
     * @param ApruveConfigInterface $config
     *
     * @return ApruveOrderInterface
     */
    public function createFromOrder(Order $order, ApruveConfigInterface $config);
}
