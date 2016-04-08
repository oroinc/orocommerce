<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Option;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\User;

class UserTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOption()
    {
        return new User();
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
                    'The required option "USER" is missing.',
                ],
            ],
            'invalid type' => [
                ['USER' => 123],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "USER" with value 123 is expected to be of type "string", but is of ' .
                    'type "integer".',
                ],
            ],
            'valid' => [['USER' => 'username'], ['USER' => 'username']],
        ];
    }
}
