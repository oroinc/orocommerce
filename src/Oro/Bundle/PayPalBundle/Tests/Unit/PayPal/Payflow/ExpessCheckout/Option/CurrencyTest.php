<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;

class CurrencyTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new ECOption\Currency(), new ECOption\Action()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['CURRENCY' => 'UAH', ECOption\Action::ACTION => ECOption\Action::SET_EC],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "CURRENCY" with value "UAH" is invalid. Accepted values are: "AUD", "CAD", "EUR", ' .
                    '"GBP", "JPY", "USD".',
                ],
            ],
            'not allowed for non SET_EC' => [
                ['CURRENCY' => 'USD', ECOption\Action::ACTION => ECOption\Action::DO_EC],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException',
                    'The option "CURRENCY" does not exist. Defined options are: "ACTION".'
                ]
            ],
            'valid' => [
                ['CURRENCY' => 'USD', ECOption\Action::ACTION => ECOption\Action::SET_EC],
                ['CURRENCY' => 'USD', ECOption\Action::ACTION => ECOption\Action::SET_EC]
            ],
        ];
    }
}
