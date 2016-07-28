<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

class AmountTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new Option\Amount()];
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
                    'The required option "AMT" is missing.',
                ],
            ],
            'override using provided amounts' => [
                ['AMT' => 10, 'ITEMAMT' => 100],
                ['AMT' => '100.00', 'ITEMAMT' => '100.00'],
            ],
            'sum amounts' => [
                ['AMT' => 10, 'ITEMAMT' => 100, 'FREIGHTAMT' => 10],
                [
                    'AMT' => '110.00',
                    'ITEMAMT' => '100.00',
                    'FREIGHTAMT' => '10.00',
                ],
            ],
            'use value from options' => [
                ['AMT' => 10],
                ['AMT' => '10.00'],
            ],
        ];
    }

    public function testAmountNotRequired()
    {
        $option = new Option\Amount(false);
        $resolver = new Option\OptionsResolver();
        $option->configureOption($resolver);
        $resolver->resolve([]);
    }
}
