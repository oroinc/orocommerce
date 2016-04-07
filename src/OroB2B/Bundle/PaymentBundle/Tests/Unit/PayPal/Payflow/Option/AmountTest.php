<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Option;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class AmountTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOption()
    {
        return new Option\Amount();
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
                [Option\Amount::AMT => 10, Option\Amount::ITEMAMT => 100],
                [Option\Amount::AMT => 100, Option\Amount::ITEMAMT => 100],
            ],
            'sum amounts' => [
                [Option\Amount::AMT => 10, Option\Amount::ITEMAMT => 100, Option\Amount::FREIGHTAMT => 10],
                [Option\Amount::AMT => 110, Option\Amount::ITEMAMT => 100, Option\Amount::FREIGHTAMT => 10],
            ],
            'use value from options' => [
                [Option\Amount::AMT => 10],
                [Option\Amount::AMT => 10],
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
