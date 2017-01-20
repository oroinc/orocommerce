<?php

namespace Oro\Bundle\MoneyOrderBundle\Method;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;

class MoneyOrderMethodProvider implements PaymentMethodProviderInterface
{
     /**
     * @return PaymentMethodInterface[]
     */
    public function getPaymentMethods()
    {
        $paymentMethod = new MoneyOrder();
        return [$paymentMethod->getIdentifier() => $paymentMethod];
    }

    /**
     * @param string $identifier
     * @return PaymentMethodInterface
     */
    public function getPaymentMethod($identifier)
    {
        if ($this->hasPaymentMethod($identifier)) {
            return $this->getPaymentMethods()[$identifier];
        }
        return null;
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPaymentMethod($identifier)
    {
        $paymentMethods = $this->getPaymentMethods();

        return isset($paymentMethods[$identifier]);
    }
}
