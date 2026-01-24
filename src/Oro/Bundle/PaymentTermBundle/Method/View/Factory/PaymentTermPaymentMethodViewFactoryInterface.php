<?php

namespace Oro\Bundle\PaymentTermBundle\Method\View\Factory;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;

/**
 * Factory interface for creating payment term payment method view instances.
 *
 * Implementations of this interface are responsible for creating {@see PaymentMethodViewInterface} instances
 * from {@see PaymentTermConfigInterface} configurations, allowing the payment method view system
 * to instantiate views for payment term payment methods.
 */
interface PaymentTermPaymentMethodViewFactoryInterface
{
    /**
     * @param PaymentTermConfigInterface $config
     * @return PaymentMethodViewInterface
     */
    public function create(PaymentTermConfigInterface $config);
}
