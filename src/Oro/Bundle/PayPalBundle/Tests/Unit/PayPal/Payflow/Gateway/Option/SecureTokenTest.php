<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option\SecureToken;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class SecureTokenTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new SecureToken()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        $token = md5('SecureToken');

        return [
            'empty' => [],
            'invalid type' => [
                ['SECURETOKEN' => 12345],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "SECURETOKEN" with value 12345 is expected to be of type "string", but is of '
                    . 'type "int".',
                ],
            ],
            'valid' => [['SECURETOKEN' => $token], ['SECURETOKEN' => $token]],
        ];
    }
}
