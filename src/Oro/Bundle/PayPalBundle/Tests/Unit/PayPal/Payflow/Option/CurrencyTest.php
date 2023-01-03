<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Currency;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class CurrencyTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new Currency(false)];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['CURRENCY' => 'UAH'],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "CURRENCY" with value "UAH" is invalid. Accepted values are: "AUD", "CAD", "EUR", '
                    . '"GBP", "JPY", "USD".',
                ],
            ],
            'valid' => [['CURRENCY' => 'USD'], ['CURRENCY' => 'USD']],
        ];
    }
}
