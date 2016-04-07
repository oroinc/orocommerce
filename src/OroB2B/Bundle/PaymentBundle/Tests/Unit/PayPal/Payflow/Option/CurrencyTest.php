<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Option;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\Currency;

class CurrencyTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOption()
    {
        return new Currency();
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['CURRENCY' => 'UAH'],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "CURRENCY" with value "UAH" is invalid. Accepted values are: "AUD", "CAD", "EUR", ' .
                    '"GBP", "JPY", "USD".',
                ],
            ],
            'valid' => [['CURRENCY' => 'USD'], ['CURRENCY' => 'USD']],
        ];
    }
}
