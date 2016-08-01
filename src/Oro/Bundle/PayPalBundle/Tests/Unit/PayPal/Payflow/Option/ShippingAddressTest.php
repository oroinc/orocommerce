<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\ShippingAddress;

class ShippingAddressTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new ShippingAddress()];
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function configureOptionDataProvider()
    {
        return [
            'empty is valid' => [],
            'valid' => [
                [
                    'SHIPTOFIRSTNAME' => 'John',
                    'SHIPTOLASTNAME' => 'Doe',
                    'SHIPTOSTREET' => '123',
                    'SHIPTOSTREET2' => 'Main St',
                    'SHIPTOCITY' => 'Anytown',
                    'SHIPTOSTATE' => 'Anystate',
                    'SHIPTOZIP' => '12345',
                    'SHIPTOCOUNTRY' => '840',
                ],
                [
                    'SHIPTOFIRSTNAME' => 'John',
                    'SHIPTOLASTNAME' => 'Doe',
                    'SHIPTOSTREET' => '123',
                    'SHIPTOSTREET2' => 'Main St',
                    'SHIPTOCITY' => 'Anytown',
                    'SHIPTOSTATE' => 'Anystate',
                    'SHIPTOZIP' => '12345',
                    'SHIPTOCOUNTRY' => '840',
                ],
                [],
            ],
            'invalid SHIPTOFIRSTNAME' => [
                ['SHIPTOFIRSTNAME' => 1],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "SHIPTOFIRSTNAME" with value 1 is expected to be of type "string", but is of type ' .
                    '"integer".',
                ],
            ],
            'invalid SHIPTOLASTNAME' => [
                ['SHIPTOLASTNAME' => 1],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "SHIPTOLASTNAME" with value 1 is expected to be of type "string", but is of type ' .
                    '"integer".',
                ],
            ],
            'invalid SHIPTOSTREET' => [
                ['SHIPTOSTREET' => 1],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "SHIPTOSTREET" with value 1 is expected to be of type "string", but is of type ' .
                    '"integer".',
                ],
            ],
            'invalid SHIPTOSTREET2' => [
                ['SHIPTOSTREET2' => 1],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "SHIPTOSTREET2" with value 1 is expected to be of type "string", but is of type ' .
                    '"integer".',
                ],
            ],
            'invalid SHIPTOCITY' => [
                ['SHIPTOCITY' => 1],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "SHIPTOCITY" with value 1 is expected to be of type "string", but is of type ' .
                    '"integer".',
                ],
            ],
            'invalid SHIPTOSTATE' => [
                ['SHIPTOSTATE' => 1],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "SHIPTOSTATE" with value 1 is expected to be of type "string", but is of type ' .
                    '"integer".',
                ],
            ],
            'invalid SHIPTOZIP' => [
                ['SHIPTOZIP' => 12345],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "SHIPTOZIP" with value 12345 is expected to be of type "string", but is of type ' .
                    '"integer".',
                ],
            ],
            'invalid SHIPTOCOUNTRY' => [
                ['SHIPTOCOUNTRY' => 840],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "SHIPTOCOUNTRY" with value 840 is expected to be of type "string", but is of type ' .
                    '"integer".',
                ],
            ],
        ];
    }
}
