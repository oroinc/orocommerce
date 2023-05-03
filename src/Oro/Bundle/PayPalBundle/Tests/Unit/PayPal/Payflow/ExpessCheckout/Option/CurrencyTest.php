<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\ExpessCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class CurrencyTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new ECOption\Currency(), new ECOption\Action()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['CURRENCY' => 'UAH', ECOption\Action::ACTION => ECOption\Action::SET_EC],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "CURRENCY" with value "UAH" is invalid. Accepted values are: "AUD", "CAD", "EUR", '
                    . '"GBP", "JPY", "USD".',
                ],
            ],
            'not allowed for non SET_EC' => [
                ['CURRENCY' => 'USD', ECOption\Action::ACTION => ECOption\Action::DO_EC],
                [],
                [
                    UndefinedOptionsException::class,
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
