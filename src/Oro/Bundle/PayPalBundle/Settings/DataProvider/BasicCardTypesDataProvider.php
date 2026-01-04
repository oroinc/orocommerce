<?php

namespace Oro\Bundle\PayPalBundle\Settings\DataProvider;

class BasicCardTypesDataProvider implements CreditCardTypesDataProviderInterface
{
    /**
     * @internal
     */
    public const VISA = 'visa';

    /**
     * @internal
     */
    public const MASTERCARD = 'mastercard';

    /**
     * @internal
     */
    public const DISCOVER = 'discover';

    /**
     * @internal
     */
    public const AMERICAN_EXPRESS = 'american_express';

    #[\Override]
    public function getCardTypes()
    {
        return [
            self::VISA,
            self::MASTERCARD,
            self::DISCOVER,
            self::AMERICAN_EXPRESS,
        ];
    }

    #[\Override]
    public function getDefaultCardTypes()
    {
        return [
            self::VISA,
            self::MASTERCARD,
        ];
    }
}
