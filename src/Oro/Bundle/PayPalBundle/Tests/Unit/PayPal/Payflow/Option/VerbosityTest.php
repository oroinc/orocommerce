<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Verbosity;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class VerbosityTest extends AbstractOptionTest
{
    #[\Override]
    protected function getOptions(): array
    {
        return [new Verbosity()];
    }

    #[\Override]
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['VERBOSITY' => true],
                [],
                [
                    InvalidOptionsException::class,
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
