<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Verbosity;

class VerbosityTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new Verbosity()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['VERBOSITY' => true],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "VERBOSITY" with value true is invalid. Accepted values are: "HIGH".',
                ],
            ],
            'valid' => [
                ['VERBOSITY' => 'HIGH'],
                ['VERBOSITY' => 'HIGH'],
            ],
        ];
    }
}
