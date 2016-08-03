<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Invoice;

class InvoiceTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new Invoice()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['INVNUM' => 100001],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "INVNUM" with value 100001 is expected to be of type "string", but is of ' .
                    'type "integer".',
                ],
            ],
            'valid' => [['INVNUM' => '100001'], ['INVNUM' => '100001']],
        ];
    }
}
