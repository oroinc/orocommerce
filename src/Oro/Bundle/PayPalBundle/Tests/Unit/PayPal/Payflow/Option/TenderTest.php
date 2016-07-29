<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Tender;

class TenderTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new Tender()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [
                [],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\MissingOptionsException',
                    'The required option "TENDER" is missing.',
                ],
            ],
            'invalid type' => [
                ['TENDER' => true],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "TENDER" with value true is invalid. Accepted values are: "A", "C", "D", "K", "P".',
                ],
            ],
            'valid' => [
                ['TENDER' => 'A'],
                ['TENDER' => 'A'],
            ],
        ];
    }
}
