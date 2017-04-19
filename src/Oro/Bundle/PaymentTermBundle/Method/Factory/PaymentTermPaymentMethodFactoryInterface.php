<?php

namespace Oro\Bundle\PaymentTermBundle\Method\Factory;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;

interface PaymentTermPaymentMethodFactoryInterface
{
    /**
     * @param PaymentTermConfigInterface $config
     * @return PaymentMethodInterface
     */
    public function create(PaymentTermConfigInterface $config);
}
