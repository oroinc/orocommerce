<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\View\Factory;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;

interface MoneyOrderPaymentMethodViewFactoryInterface
{
    /**
     * @param MoneyOrderConfigInterface $config
     * @return PaymentMethodViewInterface
     */
    public function create(MoneyOrderConfigInterface $config);
}
