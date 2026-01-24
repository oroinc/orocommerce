<?php

namespace Oro\Bundle\PayPalBundle\Settings\DataProvider;

/**
 * Provides basic credit card types supported by PayPal.
 *
 * Returns the list of supported credit card types (Visa, Mastercard, Discover, American Express)
 * and the default card types for PayPal payment processing.
 */
class BasicCardTypesDataProvider implements CreditCardTypesDataProviderInterface
{
    /**
     * @internal
     */
    const VISA = 'visa';

    /**
     * @internal
     */
    const MASTERCARD = 'mastercard';

    /**
     * @internal
     */
    const DISCOVER = 'discover';

    /**
     * @internal
     */
    const AMERICAN_EXPRESS = 'american_express';

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
