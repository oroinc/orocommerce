<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;

class PaymentTypeTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new ECOption\PaymentType(), new ECOption\Action()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['PAYMENTTYPE' => 'some', ECOption\Action::ACTION => ECOption\Action::SET_EC],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "PAYMENTTYPE" with value "some" is invalid. Accepted values are: "instantonly", "any"',
                ],
            ],
            'not applicable (action GET_EC_DETAILS)' => [
                ['PAYMENTTYPE' => 'instantonly', ECOption\Action::ACTION => ECOption\Action::GET_EC_DETAILS],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException',
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
