<?php

namespace Oro\Bundle\PaymentBundle\Method\View;

interface PaymentMethodViewProviderInterface
{
    /**
     * @param array $paymentMethods
     * @return PaymentMethodViewInterface[]
     */
    public function getPaymentMethodViews($paymentMethods);

    /**
     * @param string $name
     * @return PaymentMethodViewInterface
     */
    public function getPaymentMethodView($name);

    /**
     * @param string $name
     * @return bool
     */
    public function hasPaymentMethodView($name);
}
