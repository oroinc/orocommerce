<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Password;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class PasswordTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new Password()];
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
                    'The required option "PWD" is missing.',
                ],
            ],
            'invalid type' => [
                ['PWD' => 123],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "PWD" with value 123 is expected to be of type "string", but is of '
                    . 'type "int".',
                ],
            ],
            'valid' => [['PWD' => '123'], ['PWD' => '123']],
        ];
    }
}
