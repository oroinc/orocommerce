<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Vendor;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class VendorTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new Vendor()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [
                [],
                [],
                [
                    MissingOptionsException::class,
                    'The required option "VENDOR" is missing.',
                ],
            ],
            'invalid type' => [
                ['VENDOR' => 123],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "VENDOR" with value 123 is expected to be of type "string", but is of '
                    . 'type "int".',
                ],
            ],
            'valid' => [['VENDOR' => 'username'], ['VENDOR' => 'username']],
        ];
    }
}
