<?php

namespace Oro\Bundle\PayPalBundle\Settings\DataProvider;

/**
 * Defines the contract for providing available payment actions.
 *
 * Returns the list of payment actions supported by the payment processor.
 */
interface PaymentActionsDataProviderInterface
{
    /**
     * @return string[]
     */
    public function getPaymentActions();
}
