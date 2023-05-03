<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\BillingAddress;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class BillingAddressTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new BillingAddress()];
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
                    'BILLTOFIRSTNAME' => 'John',
                    'BILLTOLASTNAME' => 'Doe',
                    'BILLTOSTREET' => '123',
                    'BILLTOSTREET2' => 'Main St',
                    'BILLTOCITY' => 'Anytown',
                    'BILLTOSTATE' => 'Anystate',
                    'BILLTOZIP' => '12345',
                    'BILLTOCOUNTRY' => '840',
                ],
                [
                    'BILLTOFIRSTNAME' => 'John',
                    'BILLTOLASTNAME' => 'Doe',
                    'BILLTOSTREET' => '123',
                    'BILLTOSTREET2' => 'Main St',
                    'BILLTOCITY' => 'Anytown',
                    'BILLTOSTATE' => 'Anystate',
                    'BILLTOZIP' => '12345',
                    'BILLTOCOUNTRY' => '840',
                ],
                [],
            ],
            'invalid BILLTOFIRSTNAME' => [
                ['BILLTOFIRSTNAME' => 1],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "BILLTOFIRSTNAME" with value 1 is expected to be of type "string", but is of type '
                    . '"int".',
                ],
            ],
            'invalid BILLTOLASTNAME' => [
                ['BILLTOLASTNAME' => 1],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "BILLTOLASTNAME" with value 1 is expected to be of type "string", but is of type '
                    . '"int".',
                ],
            ],
            'invalid BILLTOSTREET' => [
                ['BILLTOSTREET' => 1],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "BILLTOSTREET" with value 1 is expected to be of type "string", but is of type '
                    . '"int".',
                ],
            ],
            'invalid BILLTOSTREET2' => [
                ['BILLTOSTREET2' => 1],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "BILLTOSTREET2" with value 1 is expected to be of type "string", but is of type '
                    . '"int".',
                ],
            ],
            'invalid BILLTOCITY' => [
                ['BILLTOCITY' => 1],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "BILLTOCITY" with value 1 is expected to be of type "string", but is of type '
                    . '"int".',
                ],
            ],
            'invalid BILLTOSTATE' => [
                ['BILLTOSTATE' => 1],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "BILLTOSTATE" with value 1 is expected to be of type "string", but is of type '
                    . '"int".',
                ],
            ],
            'invalid BILLTOZIP' => [
                ['BILLTOZIP' => 12345],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "BILLTOZIP" with value 12345 is expected to be of type "string", but is of type '
                    . '"int".',
                ],
            ],
            'invalid BILLTOCOUNTRY' => [
                ['BILLTOCOUNTRY' => 840],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "BILLTOCOUNTRY" with value 840 is expected to be of type "string", but is of type '
                    . '"int".',
                ],
            ],
        ];
    }
}
