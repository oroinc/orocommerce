<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\ExpessCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class PayerTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new ECOption\Payer(), new ECOption\Action()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['PAYERID' => 12345, ECOption\Action::ACTION => ECOption\Action::DO_EC],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "PAYERID" with value 12345 is expected to be of type "string", but is of type "int".',
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
