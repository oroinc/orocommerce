<?php

namespace Oro\Bundle\PaymentBundle\Method\Provider;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

interface PaymentMethodProviderInterface
{
    /**
     * @return PaymentMethodInterface[]
     */
    public function getPaymentMethods();

    /**
     * @param string $identifier
     * @return PaymentMethodInterface|null
     */
    public function getPaymentMethod($identifier);

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPaymentMethod($identifier);
}
