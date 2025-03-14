<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\ExpessCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class ShippingAddressTest extends AbstractOptionTest
{
    #[\Override]
    protected function getOptions(): array
    {
        return [new ECOption\ShippingAddress(), new ECOption\Action()];
    }

    #[\Override]
    public function configureOptionDataProvider(): array
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
                    UndefinedOptionsException::class,
                    'The options "SHIPTOCITY", "SHIPTOCOUNTRY", "SHIPTOFIRSTNAME", "SHIPTOLASTNAME", "SHIPTOSTATE", '
                    . '"SHIPTOSTREET", "SHIPTOSTREET2", "SHIPTOZIP" do not exist. Defined options are: "ACTION".'
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
                    UndefinedOptionsException::class,
                    'The options "SHIPTOCITY", "SHIPTOCOUNTRY", "SHIPTOFIRSTNAME", "SHIPTOLASTNAME", "SHIPTOSTATE", '
                    . '"SHIPTOSTREET", "SHIPTOSTREET2", "SHIPTOZIP" do not exist. Defined options are: "ACTION".'
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
