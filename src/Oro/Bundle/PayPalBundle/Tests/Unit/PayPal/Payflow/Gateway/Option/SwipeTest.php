<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option\Swipe;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;

class SwipeTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new Swipe()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['SWIPE' => 123],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "SWIPE" with value 123 is expected to be of type "string", but is of ' .
                    'type "integer".',
                ],
            ],
            'valid' => [
                ['SWIPE' => ';4912000033330026=15121011000012345678'],
                ['SWIPE' => ';4912000033330026=15121011000012345678'],
            ],
        ];
    }
}
