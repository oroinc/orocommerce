<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\ExpessCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class AmountTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new ECOption\Amount(), new ECOption\Action()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'required for action SET_EC' => [
                [ECOption\Action::ACTION => ECOption\Action::SET_EC],
                [],
                [
                    MissingOptionsException::class,
                    'The required option "AMT" is missing.',
                ]
            ],
            'required for action DO_EC' => [
                [ECOption\Action::ACTION => ECOption\Action::SET_EC],
                [],
                [
                    MissingOptionsException::class,
                    'The required option "AMT" is missing.',
                ]
            ],
            'not allowed for action GET_EC_DETAILS' => [
                ['AMT' => '10.00', ECOption\Action::ACTION => ECOption\Action::GET_EC_DETAILS],
                [],
                [
                    UndefinedOptionsException::class,
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
