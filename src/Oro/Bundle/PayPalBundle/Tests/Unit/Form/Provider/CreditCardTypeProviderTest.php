<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Form\Provider;

use Oro\Bundle\PayPalBundle\Entity\CreditCardTypes;
use Oro\Bundle\PayPalBundle\Form\Provider\CreditCardTypeProvider;

class CreditCardTypeProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGet()
    {
        $types = [
            'Visa' => CreditCardTypes::CARD_VISA,
            'Mastercard' => CreditCardTypes::CARD_MASTERCARD,
            'Discover' => CreditCardTypes::CARD_DISCOVER,
            'American Express' => CreditCardTypes::CARD_AMERICAN_EXPRESS,
        ];

        static::assertSame($types, CreditCardTypeProvider::get());
    }
}
