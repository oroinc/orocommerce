<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Transaction;

class TransactionTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new Transaction()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [
                [],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\MissingOptionsException',
                    'The required option "TRXTYPE" is missing.',
                ],
            ],
            'invalid type' => [
                ['TRXTYPE' => true],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "TRXTYPE" with value true is invalid. Accepted values are: "A", "B", "C", "L", "D", ' .
                    '"N", "I", "K", "S", "F", "V".',
                ],
            ],
            'valid' => [
                ['TRXTYPE' => 'S'],
                ['TRXTYPE' => 'S'],
            ],
        ];
    }
}
