<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Option;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\Tender;

class TenderTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOption()
    {
        return new Tender();
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
