<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\ButtonSource;

class ButtonSourceTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new ButtonSource()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['BUTTONSOURCE' => 100001],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "BUTTONSOURCE" with value 100001 is expected to be of type "string", but is of ' .
                    'type "integer".',
                ],
            ],
            'valid' => [['BUTTONSOURCE' => 'OroCommerce_SP'], ['BUTTONSOURCE' => 'OroCommerce_SP']],
        ];
    }
}
