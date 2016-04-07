<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Option;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\Transaction;

class TransactionTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOption()
    {
        return new Transaction();
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
