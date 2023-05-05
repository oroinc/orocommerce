<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Transaction;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class TransactionTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new Transaction()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [
                [],
                [],
                [
                    MissingOptionsException::class,
                    'The required option "TRXTYPE" is missing.',
                ],
            ],
            'invalid type' => [
                ['TRXTYPE' => true],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "TRXTYPE" with value true is invalid. Accepted values are: "A", "B", "C", "L", "D", '
                    . '"N", "I", "K", "S", "F", "V".',
                ],
            ],
            'valid' => [
                ['TRXTYPE' => 'S'],
                ['TRXTYPE' => 'S'],
            ],
        ];
    }
}
