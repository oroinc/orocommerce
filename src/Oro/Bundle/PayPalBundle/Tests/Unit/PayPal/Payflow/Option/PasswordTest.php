<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Password;

class PasswordTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new Password()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [
                [],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\MissingOptionsException',
                    'The required option "PWD" is missing.',
                ],
            ],
            'invalid type' => [
                ['PWD' => 123],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "PWD" with value 123 is expected to be of type "string", but is of ' .
                    'type "integer".',
                ],
            ],
            'valid' => [['PWD' => '123'], ['PWD' => '123']],
        ];
    }
}
