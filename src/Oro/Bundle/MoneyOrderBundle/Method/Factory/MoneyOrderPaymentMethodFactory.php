<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Factory;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\MoneyOrderBundle\Method\MoneyOrder;

/**
 * Creates Money Order payment method instances from configuration objects.
 *
 * This factory instantiates {@see MoneyOrder} payment method objects from {@see MoneyOrderConfig} instances,
 * providing a centralized way to construct payment method objects with proper configuration.
 * It implements the factory pattern to decouple payment method creation from configuration management.
 */
class MoneyOrderPaymentMethodFactory implements MoneyOrderPaymentMethodFactoryInterface
{
    #[\Override]
    public function create(MoneyOrderConfigInterface $config)
    {
        return new MoneyOrder($config);
    }
}
