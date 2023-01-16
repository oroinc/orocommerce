<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option\Customer;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class CustomerTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new Customer()];
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
                    'The required option "ACCT" is missing.',
                ],
            ],
            'invalid type' => [
                ['ACCT' => 1234567890123456],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "ACCT" with value 1234567890123456 is expected to be of type "string", but is of '
                    . 'type "int".',
                ],
            ],
            'valid' => [['ACCT' => '4111111111111111'], ['ACCT' => '4111111111111111']],
        ];
    }
}
