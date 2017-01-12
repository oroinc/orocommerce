<?php

namespace Oro\Bundle\PaymentBundle\Method\View;

interface PaymentMethodViewProviderInterface
{
    /**
     * @param array $identifiers
     * @return PaymentMethodViewInterface[]
     */
    public function getPaymentMethodViews($identifiers);

    /**
     * @param string $identifier
     * @return PaymentMethodViewInterface|null
     */
    public function getPaymentMethodView($identifier);

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPaymentMethodView($identifier);
}
