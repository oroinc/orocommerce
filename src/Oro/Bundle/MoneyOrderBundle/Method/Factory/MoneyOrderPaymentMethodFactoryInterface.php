<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Factory;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

interface MoneyOrderPaymentMethodFactoryInterface
{
    /**
     * @param MoneyOrderConfigInterface $config
     * @return PaymentMethodInterface
     */
    public function create(MoneyOrderConfigInterface $config);
}
