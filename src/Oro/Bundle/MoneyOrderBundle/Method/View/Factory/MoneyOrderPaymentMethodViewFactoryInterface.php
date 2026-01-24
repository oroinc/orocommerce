<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\View\Factory;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;

/**
 * Defines the contract for creating Money Order payment method view instances.
 */
interface MoneyOrderPaymentMethodViewFactoryInterface
{
    /**
     * @param MoneyOrderConfigInterface $config
     * @return PaymentMethodViewInterface
     */
    public function create(MoneyOrderConfigInterface $config);
}
