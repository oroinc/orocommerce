<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\ExpessCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class PaymentTypeTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new ECOption\PaymentType(), new ECOption\Action()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['PAYMENTTYPE' => 'some', ECOption\Action::ACTION => ECOption\Action::SET_EC],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "PAYMENTTYPE" with value "some" is invalid. Accepted values are: "instantonly", "any"',
                ],
            ],
            'not applicable (action GET_EC_DETAILS)' => [
                ['PAYMENTTYPE' => 'instantonly', ECOption\Action::ACTION => ECOption\Action::GET_EC_DETAILS],
                [],
                [
                    UndefinedOptionsException::class,
                    'The option "PAYMENTTYPE" does not exist. Defined options are: "ACTION".'
                ]
            ],
            'valid instantonly (action SET)' => [
                ['PAYMENTTYPE' => 'instantonly', ECOption\Action::ACTION => ECOption\Action::SET_EC],
                ['PAYMENTTYPE' => 'instantonly', ECOption\Action::ACTION => ECOption\Action::SET_EC],
            ],
            'valid any (action SET)' => [
                ['PAYMENTTYPE' => 'any', ECOption\Action::ACTION => ECOption\Action::SET_EC],
                ['PAYMENTTYPE' => 'any', ECOption\Action::ACTION => ECOption\Action::SET_EC],
            ],
            'valid instantonly (action DO_EC)' => [
                ['PAYMENTTYPE' => 'instantonly', ECOption\Action::ACTION => ECOption\Action::DO_EC],
                ['PAYMENTTYPE' => 'instantonly', ECOption\Action::ACTION => ECOption\Action::DO_EC],
            ],
            'valid any (action DO_EC)' => [
                ['PAYMENTTYPE' => 'any', ECOption\Action::ACTION => ECOption\Action::DO_EC],
                ['PAYMENTTYPE' => 'any', ECOption\Action::ACTION => ECOption\Action::DO_EC],
            ],
        ];
    }
}
