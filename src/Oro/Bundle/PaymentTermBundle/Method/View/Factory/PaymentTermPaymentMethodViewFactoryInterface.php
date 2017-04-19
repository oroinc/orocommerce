<?php

namespace Oro\Bundle\PaymentTermBundle\Method\View\Factory;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;

interface PaymentTermPaymentMethodViewFactoryInterface
{
    /**
     * @param PaymentTermConfigInterface $config
     * @return PaymentMethodViewInterface
     */
    public function create(PaymentTermConfigInterface $config);
}
