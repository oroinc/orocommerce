<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option\SecureTokenIdentifier;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;

class SecureTokenIdentifierTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new SecureTokenIdentifier()];
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
