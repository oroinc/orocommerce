<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\User;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class UserTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new User()];
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
                    'The required option "USER" is missing.',
                ],
            ],
            'invalid type' => [
                ['USER' => 123],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "USER" with value 123 is expected to be of type "string", but is of '
                    . 'type "int".',
                ],
            ],
            'valid' => [['USER' => 'username'], ['USER' => 'username']],
        ];
    }
}
