<?php

namespace Oro\Bundle\PaymentBundle\Method\View;

/**
 * Defines the contract for accessing payment method view representations.
 *
 * Implementations provide access to payment method views for rendering and display,
 * supporting both single and batch retrieval of views by identifier.
 */
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
