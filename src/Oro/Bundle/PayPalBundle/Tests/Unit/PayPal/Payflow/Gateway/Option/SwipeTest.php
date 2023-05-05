<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option\Swipe;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class SwipeTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new Swipe()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['SWIPE' => 123],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "SWIPE" with value 123 is expected to be of type "string", but is of type "int".',
                ],
            ],
            'valid' => [
                ['SWIPE' => ';4912000033330026=15121011000012345678'],
                ['SWIPE' => ';4912000033330026=15121011000012345678'],
            ],
        ];
    }
}
