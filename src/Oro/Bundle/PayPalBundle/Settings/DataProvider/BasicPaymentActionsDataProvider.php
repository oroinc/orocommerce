<?php

namespace Oro\Bundle\PayPalBundle\Settings\DataProvider;

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
