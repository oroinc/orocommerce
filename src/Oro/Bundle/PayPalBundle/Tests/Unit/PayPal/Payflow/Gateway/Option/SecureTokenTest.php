<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option\SecureToken;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;

class SecureTokenTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new SecureToken()];
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
