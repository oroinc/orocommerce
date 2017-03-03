<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\View\Factory;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\MoneyOrderBundle\Method\View\MoneyOrderView;

class MoneyOrderPaymentMethodViewFactory implements MoneyOrderPaymentMethodViewFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(MoneyOrderConfigInterface $config)
    {
        return new MoneyOrderView($config);
    }
}
