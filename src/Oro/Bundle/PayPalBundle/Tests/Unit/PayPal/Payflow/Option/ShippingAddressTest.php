<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\ShippingAddress;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class ShippingAddressTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new ShippingAddress()];
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function configureOptionDataProvider(): array
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
                    InvalidOptionsException::class,
                    'The option "SHIPTOFIRSTNAME" with value 1 is expected to be of type "string", but is of type '
                    . '"int".',
                ],
            ],
            'invalid SHIPTOLASTNAME' => [
                ['SHIPTOLASTNAME' => 1],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "SHIPTOLASTNAME" with value 1 is expected to be of type "string", but is of type '
                    . '"int".',
                ],
            ],
            'invalid SHIPTOSTREET' => [
                ['SHIPTOSTREET' => 1],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "SHIPTOSTREET" with value 1 is expected to be of type "string", but is of type '
                    . '"int".',
                ],
            ],
            'invalid SHIPTOSTREET2' => [
                ['SHIPTOSTREET2' => 1],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "SHIPTOSTREET2" with value 1 is expected to be of type "string", but is of type '
                    . '"int".',
                ],
            ],
            'invalid SHIPTOCITY' => [
                ['SHIPTOCITY' => 1],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "SHIPTOCITY" with value 1 is expected to be of type "string", but is of type '
                    . '"int".',
                ],
            ],
            'invalid SHIPTOSTATE' => [
                ['SHIPTOSTATE' => 1],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "SHIPTOSTATE" with value 1 is expected to be of type "string", but is of type '
                    . '"int".',
                ],
            ],
            'invalid SHIPTOZIP' => [
                ['SHIPTOZIP' => 12345],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "SHIPTOZIP" with value 12345 is expected to be of type "string", but is of type '
                    . '"int".',
                ],
            ],
            'invalid SHIPTOCOUNTRY' => [
                ['SHIPTOCOUNTRY' => 840],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "SHIPTOCOUNTRY" with value 840 is expected to be of type "string", but is of type '
                    . '"int".',
                ],
            ],
        ];
    }
}
