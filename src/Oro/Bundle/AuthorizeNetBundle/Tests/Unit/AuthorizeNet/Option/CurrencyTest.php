<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Option;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class CurrencyTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new Option\Currency(false)];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'invalid type' => [
                ['currency' => 'UAH'],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "currency" with value "UAH" is invalid. Accepted values are: "AUD", "USD", "CAD", '.
                    '"EUR", "GBP", "NZD".',
                ],
            ],
            'valid' => [
                ['currency' => 'USD'],
                ['currency' => 'USD'],
            ],
        ];
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required option "currency" is missing.
     */
    public function testRequired()
    {
        $resolver = new Option\OptionsResolver();
        $currency = new Option\Currency();

        $resolver->addOption($currency);
        $resolver->resolve();
    }
}
