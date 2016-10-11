<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;

class InvoiceTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new ECOption\Invoice(), new ECOption\Action()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['INVNUM' => 100001, ECOption\Action::ACTION => ECOption\Action::SET_EC],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "INVNUM" with value 100001 is expected to be of type "string", but is of ' .
                    'type "integer".',
                ],
            ],
            'not allowed without action' => [
                ['INVNUM' => '100001'],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException',
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
