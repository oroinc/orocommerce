<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\View\Factory;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\MoneyOrderBundle\Method\View\MoneyOrderView;

/**
 * Creates Money Order payment method view instances from configuration objects.
 *
 * This factory instantiates MoneyOrderView objects from {@see MoneyOrderConfig} instances, providing
 * a centralized way to construct payment method view objects for rendering in the storefront
 * and checkout process.
 */
class MoneyOrderPaymentMethodViewFactory implements MoneyOrderPaymentMethodViewFactoryInterface
{
    #[\Override]
    public function create(MoneyOrderConfigInterface $config)
    {
        return new MoneyOrderView($config);
    }
}
