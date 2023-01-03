<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\ExpessCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class ShippingAddressOverrideTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new ECOption\ShippingAddressOverride(), new ECOption\Action()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid action DO_EC' => [
                ['ADDROVERRIDE' => true, ECOption\Action::ACTION => ECOption\Action::DO_EC],
                [],
                [
                    UndefinedOptionsException::class,
                    'The option "ADDROVERRIDE" does not exist. Defined options are: "ACTION".'
                ]
            ],
            'invalid action GET_EC_DETAILS' => [
                ['ADDROVERRIDE' => true, ECOption\Action::ACTION => ECOption\Action::GET_EC_DETAILS],
                [],
                [
                    UndefinedOptionsException::class,
                    'The option "ADDROVERRIDE" does not exist. Defined options are: "ACTION".'
                ]
            ],
            'valid action SET_EC' => [
                ['ADDROVERRIDE' => true, ECOption\Action::ACTION => ECOption\Action::SET_EC],
                ['ADDROVERRIDE' => 1, ECOption\Action::ACTION => ECOption\Action::SET_EC],
            ]
        ];
    }
}
