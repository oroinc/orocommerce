<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\ExpessCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class InvoiceTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new ECOption\Invoice(), new ECOption\Action()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['INVNUM' => 100001, ECOption\Action::ACTION => ECOption\Action::SET_EC],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "INVNUM" with value 100001 is expected to be of type "string", but is of type "int".',
                ],
            ],
            'not allowed without action' => [
                ['INVNUM' => '100001'],
                [],
                [
                    UndefinedOptionsException::class,
                    'The option "INVNUM" does not exist. Defined options are: "ACTION".'
                ]
            ],
            'valid with action SET_EC' => [
                ['INVNUM' => '100001', ECOption\Action::ACTION => ECOption\Action::SET_EC],
                ['INVNUM' => '100001', ECOption\Action::ACTION => ECOption\Action::SET_EC]
            ],
            'valid with action DO_EC' => [
                ['INVNUM' => '100001', ECOption\Action::ACTION => ECOption\Action::DO_EC],
                ['INVNUM' => '100001', ECOption\Action::ACTION => ECOption\Action::DO_EC]
            ],
        ];
    }
}
