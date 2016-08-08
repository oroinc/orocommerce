<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;

class TenderTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new ECOption\Tender()];
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
                ['TENDER' => 'S'],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
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
