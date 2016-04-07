<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Option;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\ExpirationDate;

class ExpirationDateTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOption()
    {
        return new ExpirationDate();
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['EXPDATE' => 1459343942],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "EXPDATE" with value 1459343942 is expected to be of type "string", but is of ' .
                    'type "integer".',
                ],
            ],
            'valid' => [['EXPDATE' => '0218'], ['EXPDATE' => '0218']],
        ];
    }
}
