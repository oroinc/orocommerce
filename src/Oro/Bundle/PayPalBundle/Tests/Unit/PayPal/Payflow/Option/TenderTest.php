<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Tender;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class TenderTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new Tender()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [
                [],
                [],
                [
                    MissingOptionsException::class,
                    'The required option "TENDER" is missing.',
                ],
            ],
            'invalid type' => [
                ['TENDER' => true],
                [],
                [
                    InvalidOptionsException::class,
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
