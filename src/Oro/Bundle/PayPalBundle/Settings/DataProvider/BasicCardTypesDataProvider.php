<?php

namespace Oro\Bundle\PayPalBundle\Settings\DataProvider;

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

    /**
     * {@inheritdoc}
     */
    public function getCardTypes()
    {
        return [
            self::VISA,
            self::MASTERCARD,
            self::DISCOVER,
            self::AMERICAN_EXPRESS,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultCardTypes()
    {
        return [
            self::VISA,
            self::MASTERCARD,
        ];
    }
}
