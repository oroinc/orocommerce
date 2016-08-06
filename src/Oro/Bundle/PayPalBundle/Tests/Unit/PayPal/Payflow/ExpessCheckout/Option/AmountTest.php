<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;

class AmountTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new ECOption\Amount(), new ECOption\Action()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'required for action SET_EC' => [
                [ECOption\Action::ACTION => ECOption\Action::SET_EC],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\MissingOptionsException',
                    'The required option "AMT" is missing.',
                ]
            ],
            'required for action DO_EC' => [
                [ECOption\Action::ACTION => ECOption\Action::SET_EC],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\MissingOptionsException',
                    'The required option "AMT" is missing.',
                ]
            ],
            'not allowed for action GET_EC_DETAILS' => [
                ['AMT' => '10.00', ECOption\Action::ACTION => ECOption\Action::GET_EC_DETAILS],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException',
                    'The option "AMT" does not exist. Defined options are: "ACTION".'
                ]
            ],
            'allowed (not required) without action' => [
                ['AMT' => '10.00'],
                ['AMT' => '10.00'],
            ],
        ];
    }
}
