<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option\ExpirationDate;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class ExpirationDateTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new ExpirationDate()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['EXPDATE' => 1459343942],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "EXPDATE" with value 1459343942 is expected to be of type "string", but is of '
                    . 'type "int".',
                ],
            ],
            'valid' => [['EXPDATE' => '0218'], ['EXPDATE' => '0218']],
        ];
    }
}
