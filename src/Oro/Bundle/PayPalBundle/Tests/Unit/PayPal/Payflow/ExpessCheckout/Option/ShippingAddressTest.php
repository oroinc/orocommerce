<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;

class ShippingAddressTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new ECOption\ShippingAddress(), new ECOption\Action()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'not applicable' => [
                [
                    'SHIPTOFIRSTNAME' => 'Firstname',
                    'SHIPTOLASTNAME' => 'Lastname',
                    'SHIPTOSTREET' => 'Street',
                    'SHIPTOSTREET2' => 'Street2',
                    'SHIPTOCITY' => 'City',
                    'SHIPTOSTATE' => 'State',
                    'SHIPTOZIP' => 'Zip',
                    'SHIPTOCOUNTRY' => 'US',
                ],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException',
                    'The options "SHIPTOCITY", "SHIPTOCOUNTRY", "SHIPTOFIRSTNAME", "SHIPTOLASTNAME", "SHIPTOSTATE", ' .
                    '"SHIPTOSTREET", "SHIPTOSTREET2", "SHIPTOZIP" do not exist. Defined options are: "ACTION".'
                ]
            ],
            'valid with GET_EC_DETAILS' => [
                [
                    ECOption\Action::ACTION => ECOption\Action::GET_EC_DETAILS,
                    'SHIPTOFIRSTNAME' => 'Firstname',
                    'SHIPTOLASTNAME' => 'Lastname',
                    'SHIPTOSTREET' => 'Street',
                    'SHIPTOSTREET2' => 'Street2',
                    'SHIPTOCITY' => 'City',
                    'SHIPTOSTATE' => 'State',
                    'SHIPTOZIP' => 'Zip',
                    'SHIPTOCOUNTRY' => 'US',
                ],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException',
                    'The options "SHIPTOCITY", "SHIPTOCOUNTRY", "SHIPTOFIRSTNAME", "SHIPTOLASTNAME", "SHIPTOSTATE", ' .
                    '"SHIPTOSTREET", "SHIPTOSTREET2", "SHIPTOZIP" do not exist. Defined options are: "ACTION".'
                ]
            ],
            'valid with SET_EC' => [
                [
                    ECOption\Action::ACTION => ECOption\Action::SET_EC,
                    'SHIPTOFIRSTNAME' => 'Firstname',
                    'SHIPTOLASTNAME' => 'Lastname',
                    'SHIPTOSTREET' => 'Street',
                    'SHIPTOSTREET2' => 'Street2',
                    'SHIPTOCITY' => 'City',
                    'SHIPTOSTATE' => 'State',
                    'SHIPTOZIP' => 'Zip',
                    'SHIPTOCOUNTRY' => 'US',
                ],
                [
                    ECOption\Action::ACTION => ECOption\Action::SET_EC,
                    'SHIPTOFIRSTNAME' => 'Firstname',
                    'SHIPTOLASTNAME' => 'Lastname',
                    'SHIPTOSTREET' => 'Street',
                    'SHIPTOSTREET2' => 'Street2',
                    'SHIPTOCITY' => 'City',
                    'SHIPTOSTATE' => 'State',
                    'SHIPTOZIP' => 'Zip',
                    'SHIPTOCOUNTRY' => 'US',
                ]
            ],
            'valid with DO_EC' => [
                [
                    ECOption\Action::ACTION => ECOption\Action::DO_EC,
                    'SHIPTOFIRSTNAME' => 'Firstname',
                    'SHIPTOLASTNAME' => 'Lastname',
                    'SHIPTOSTREET' => 'Street',
                    'SHIPTOSTREET2' => 'Street2',
                    'SHIPTOCITY' => 'City',
                    'SHIPTOSTATE' => 'State',
                    'SHIPTOZIP' => 'Zip',
                    'SHIPTOCOUNTRY' => 'US',
                ],
                [
                    ECOption\Action::ACTION => ECOption\Action::DO_EC,
                    'SHIPTOFIRSTNAME' => 'Firstname',
                    'SHIPTOLASTNAME' => 'Lastname',
                    'SHIPTOSTREET' => 'Street',
                    'SHIPTOSTREET2' => 'Street2',
                    'SHIPTOCITY' => 'City',
                    'SHIPTOSTATE' => 'State',
                    'SHIPTOZIP' => 'Zip',
                    'SHIPTOCOUNTRY' => 'US',
                ]
            ],
        ];
    }
}
