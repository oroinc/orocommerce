<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Option;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\Purchase;

class PurchaseTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOption()
    {
        return new Purchase();
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['PONUM' => 100001],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "PONUM" with value 100001 is expected to be of type "string", but is of ' .
                    'type "integer".',
                ],
            ],
            'valid' => [['PONUM' => '100001'], ['PONUM' => '100001']],
        ];
    }
}
