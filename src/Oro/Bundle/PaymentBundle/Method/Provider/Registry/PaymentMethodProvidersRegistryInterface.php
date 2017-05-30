<?php

namespace Oro\Bundle\PaymentBundle\Method\Provider\Registry;

use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;

/**
 * @deprecated since 1.1
 */
interface PaymentMethodProvidersRegistryInterface
{
    /**
     * @return PaymentMethodProviderInterface[]
     */
    public function getPaymentMethodProviders();
}
