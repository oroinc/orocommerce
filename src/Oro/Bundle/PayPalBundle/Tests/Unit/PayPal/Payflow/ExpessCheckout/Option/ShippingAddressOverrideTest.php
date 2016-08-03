<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;

class ShippingAddressOverrideTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new ECOption\ShippingAddressOverride(), new ECOption\Action()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid action DO_EC' => [
                ['ADDROVERRIDE' => true, ECOption\Action::ACTION => ECOption\Action::DO_EC],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException',
                    'The option "ADDROVERRIDE" does not exist. Defined options are: "ACTION".'
                ]
            ],
            'invalid action GET_EC_DETAILS' => [
                ['ADDROVERRIDE' => true, ECOption\Action::ACTION => ECOption\Action::GET_EC_DETAILS],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException',
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
