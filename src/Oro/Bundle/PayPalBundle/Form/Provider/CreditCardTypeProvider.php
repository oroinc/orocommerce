<?php

namespace Oro\Bundle\PayPalBundle\Form\Provider;

use Oro\Bundle\PayPalBundle\Entity\CreditCardTypes;

class CreditCardTypeProvider
{
    /**
     * @return array
     */
    public static function get()
    {
        return [
            'Visa' => CreditCardTypes::CARD_VISA,
            'Mastercard' => CreditCardTypes::CARD_MASTERCARD,
            'Discover' => CreditCardTypes::CARD_DISCOVER,
            'American Express' => CreditCardTypes::CARD_AMERICAN_EXPRESS,
        ];
    }
}
