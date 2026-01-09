<?php

namespace Oro\Bundle\PaymentTermBundle\Method\Factory;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;

/**
 * Factory interface for creating payment term payment method instances.
 *
 * Implementations of this interface are responsible for creating {@see PaymentMethodInterface} instances
 * from {@see PaymentTermConfigInterface} configurations, allowing the payment method system
 * to instantiate payment term payment methods.
 */
interface PaymentTermPaymentMethodFactoryInterface
{
    /**
     * @param PaymentTermConfigInterface $config
     * @return PaymentMethodInterface
     */
    public function create(PaymentTermConfigInterface $config);
}
