<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\ExpessCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class TenderTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new ECOption\Tender()];
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
                ['TENDER' => 'S'],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "TENDER" with value "S" is invalid. Accepted values are: "P".',
                ],
            ],
            'valid' => [
                ['TENDER' => 'P'],
                ['TENDER' => 'P'],
            ],
        ];
    }
}
