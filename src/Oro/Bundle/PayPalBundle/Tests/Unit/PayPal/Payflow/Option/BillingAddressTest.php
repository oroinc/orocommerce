<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\BillingAddress;

class BillingAddressTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new BillingAddress()];
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
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "BILLTOFIRSTNAME" with value 1 is expected to be of type "string", but is of type ' .
                    '"integer".',
                ],
            ],
            'invalid BILLTOLASTNAME' => [
                ['BILLTOLASTNAME' => 1],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "BILLTOLASTNAME" with value 1 is expected to be of type "string", but is of type ' .
                    '"integer".',
                ],
            ],
            'invalid BILLTOSTREET' => [
                ['BILLTOSTREET' => 1],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "BILLTOSTREET" with value 1 is expected to be of type "string", but is of type ' .
                    '"integer".',
                ],
            ],
            'invalid BILLTOSTREET2' => [
                ['BILLTOSTREET2' => 1],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "BILLTOSTREET2" with value 1 is expected to be of type "string", but is of type ' .
                    '"integer".',
                ],
            ],
            'invalid BILLTOCITY' => [
                ['BILLTOCITY' => 1],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "BILLTOCITY" with value 1 is expected to be of type "string", but is of type ' .
                    '"integer".',
                ],
            ],
            'invalid BILLTOSTATE' => [
                ['BILLTOSTATE' => 1],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "BILLTOSTATE" with value 1 is expected to be of type "string", but is of type ' .
                    '"integer".',
                ],
            ],
            'invalid BILLTOZIP' => [
                ['BILLTOZIP' => 12345],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "BILLTOZIP" with value 12345 is expected to be of type "string", but is of type ' .
                    '"integer".',
                ],
            ],
            'invalid BILLTOCOUNTRY' => [
                ['BILLTOCOUNTRY' => 840],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "BILLTOCOUNTRY" with value 840 is expected to be of type "string", but is of type ' .
                    '"integer".',
                ],
            ],
        ];
    }
}
