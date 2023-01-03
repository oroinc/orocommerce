<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Invoice;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class InvoiceTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new Invoice()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['INVNUM' => 100001],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "INVNUM" with value 100001 is expected to be of type "string", but is of '
                    . 'type "int".',
                ],
            ],
            'valid' => [['INVNUM' => '100001'], ['INVNUM' => '100001']],
        ];
    }
}
