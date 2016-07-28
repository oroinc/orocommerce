<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Currency;

class CurrencyTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new Currency(false)];
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
