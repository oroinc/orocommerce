<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option\SecureTokenIdentifier;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class SecureTokenIdentifierTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new SecureTokenIdentifier()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        $token = UUIDGenerator::v4();

        return [
            'empty' => [],
            'invalid type' => [
                ['SECURETOKENID' => 12345],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "SECURETOKENID" with value 12345 is expected to be of type "string", but is of '
                    . 'type "int".',
                ],
            ],
            'valid' => [['SECURETOKENID' => $token], ['SECURETOKENID' => $token]],
        ];
    }
}
