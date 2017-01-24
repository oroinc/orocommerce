<?php

namespace Oro\Bundle\PaymentBundle\Method\View;

interface PaymentMethodViewProvidersRegistryInterface
{
    /**
     * @param array $methodIdentifiers
     * @return PaymentMethodViewInterface[]
     */
    public function getPaymentMethodViews(array $methodIdentifiers);

    /**
     * @param string $methodIdentifier
     * @return PaymentMethodViewInterface
     */
    public function getPaymentMethodView($methodIdentifier);
}
