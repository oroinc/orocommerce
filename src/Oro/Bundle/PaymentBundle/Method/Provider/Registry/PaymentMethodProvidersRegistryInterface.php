<?php

namespace Oro\Bundle\PaymentBundle\Method\Provider\Registry;

use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;

interface PaymentMethodProvidersRegistryInterface
{
    /**
     * @return PaymentMethodProviderInterface[]
     */
    public function getPaymentMethodProviders();
}
