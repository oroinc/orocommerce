<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\ButtonSource;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class ButtonSourceTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new ButtonSource()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['BUTTONSOURCE' => 100001],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "BUTTONSOURCE" with value 100001 is expected to be of type "string", but is of '
                    . 'type "int".',
                ],
            ],
            'valid' => [['BUTTONSOURCE' => 'OroCommerce_SP'], ['BUTTONSOURCE' => 'OroCommerce_SP']],
        ];
    }
}
