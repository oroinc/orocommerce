<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\IPAddress;

class IPAddressTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new IPAddress()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['CUSTIP' => new \stdClass()],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "CUSTIP" with value stdClass is expected to be of type "string", but is of ' .
                    'type "stdClass".',
                ],
            ],
            'valid' => [['CUSTIP' => '0.0.0.0'], ['CUSTIP' => '0.0.0.0']],
        ];
    }
}
