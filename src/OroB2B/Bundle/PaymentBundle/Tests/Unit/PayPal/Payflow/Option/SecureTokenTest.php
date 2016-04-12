<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Option;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\SecureToken;

class SecureTokenTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOption()
    {
        return new SecureToken();
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        $token = md5('SecureToken');

        return [
            'empty' => [],
            'invalid type' => [
                ['SECURETOKEN' => 12345],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "SECURETOKEN" with value 12345 is expected to be of type "string", but is of ' .
                    'type "integer".',
                ],
            ],
            'valid' => [['SECURETOKEN' => $token], ['SECURETOKEN' => $token]],
        ];
    }
}
