<?php

namespace Oro\Bundle\PayPalBundle\Settings\DataProvider;

/**
 * Provides basic payment actions supported by PayPal.
 *
 * Returns the list of supported payment actions (authorize, charge) for PayPal payment processing.
 */
class BasicPaymentActionsDataProvider implements PaymentActionsDataProviderInterface
{
    /**
     * @internal
     */
    public const AUTHORIZE = 'authorize';

    /**
     * @internal
     */
    public const CHARGE = 'charge';

    /**
     * @return string[]
     */
    #[\Override]
    public function getPaymentActions()
    {
        return [
            self::AUTHORIZE,
            self::CHARGE,
        ];
    }
}
