<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\SecureTokenIdentifier;

class SecureTokenIdentifierTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOption()
    {
        return new SecureTokenIdentifier();
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        $token = UUIDGenerator::v4();

        return [
            'empty' => [],
            'invalid type' => [
                ['SECURETOKENID' => 12345],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "SECURETOKENID" with value 12345 is expected to be of type "string", but is of ' .
                    'type "integer".',
                ],
            ],
            'valid' => [['SECURETOKENID' => $token], ['SECURETOKENID' => $token]],
        ];
    }
}
