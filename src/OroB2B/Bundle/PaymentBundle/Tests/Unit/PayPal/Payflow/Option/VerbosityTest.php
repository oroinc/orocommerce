<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Option;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\Verbosity;

class VerbosityTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOption()
    {
        return new Verbosity();
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
