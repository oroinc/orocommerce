<?php

namespace Oro\Bundle\PaymentBundle\Method\View;

interface PaymentMethodViewProviderInterface
{
    /**
     * @param array $identifiers
     * @return PaymentMethodViewInterface[]
     */
    public function getPaymentMethodViews(array $identifiers);

    /**
     * @param string $identifier
     * @return PaymentMethodViewInterface
     */
    public function getPaymentMethodView($identifier);

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPaymentMethodView($identifier);
}
