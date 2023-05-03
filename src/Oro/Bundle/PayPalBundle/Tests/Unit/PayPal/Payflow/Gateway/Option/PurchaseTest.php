<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option\Purchase;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class PurchaseTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new Purchase()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['PONUM' => 100001],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "PONUM" with value 100001 is expected to be of type "string", but is of type "int".',
                ],
            ],
            'valid' => [['PONUM' => '100001'], ['PONUM' => '100001']],
        ];
    }
}
