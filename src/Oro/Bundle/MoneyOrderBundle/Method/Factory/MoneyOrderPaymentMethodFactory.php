<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Factory;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\MoneyOrderBundle\Method\MoneyOrder;

class MoneyOrderPaymentMethodFactory implements MoneyOrderPaymentMethodFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(MoneyOrderConfigInterface $config)
    {
        return new MoneyOrder($config);
    }
}
