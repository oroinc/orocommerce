<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;

class PayerTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new ECOption\Payer(), new ECOption\Action()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['PAYERID' => 12345, ECOption\Action::ACTION => ECOption\Action::DO_EC],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "PAYERID" with value 12345 is expected to be of type "string", but is of ' .
                    'type "integer".',
                ],
            ],
            'not applicable dependency SET_EC' => [
                [ECOption\Action::ACTION => ECOption\Action::SET_EC],
                [ECOption\Action::ACTION => ECOption\Action::SET_EC],
            ],
            'not applicable dependency GET_EC_DETAILS' => [
                [ECOption\Action::ACTION => ECOption\Action::GET_EC_DETAILS],
                [ECOption\Action::ACTION => ECOption\Action::GET_EC_DETAILS],
            ],
            'valid' => [
                ['PAYERID' => 'LTKXBDLY34RT4', ECOption\Action::ACTION => ECOption\Action::DO_EC],
                ['PAYERID' => 'LTKXBDLY34RT4', ECOption\Action::ACTION => ECOption\Action::DO_EC],
            ],
        ];
    }
}
