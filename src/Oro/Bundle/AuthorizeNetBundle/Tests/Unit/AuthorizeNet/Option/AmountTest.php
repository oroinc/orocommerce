<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Option;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class AmountTest extends AbstractOptionTest
{
    /**
     * {@inheritdoc}
     */
    protected function getOptions()
    {
        return [new Option\Amount()];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [
                [],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\MissingOptionsException',
                    'The required option "amount" is missing.',
                ],
            ],
            'invalid type' => [
                ['amount' => 'twenty backs'],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "amount" with value "twenty backs" is expected to be of type "float" or "integer", '.
                    'but is of type "string".',
                ],
            ],
            'valid_float' => [
                ['amount' => 10.00],
                ['amount' => 10.00],
            ],
            'valid_integer' => [
                ['amount' => 10],
                ['amount' => 10],
            ],
        ];
    }

    public function testNotRequired()
    {
        $amount = new Option\Amount(false);
        $resolver = new Option\OptionsResolver();

        $resolver->addOption($amount);
        $resolver->resolve([]);
    }
}
