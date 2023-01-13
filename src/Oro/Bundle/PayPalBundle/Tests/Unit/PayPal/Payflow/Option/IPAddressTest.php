<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\IPAddress;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class IPAddressTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new IPAddress()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['CUSTIP' => new \stdClass()],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "CUSTIP" with value stdClass is expected to be of type "string", but is of '
                    . 'type "stdClass".',
                ],
            ],
            'valid' => [['CUSTIP' => '0.0.0.0'], ['CUSTIP' => '0.0.0.0']],
        ];
    }
}
