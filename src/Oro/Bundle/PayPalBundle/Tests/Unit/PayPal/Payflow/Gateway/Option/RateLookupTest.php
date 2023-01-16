<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option\RateLookup;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class RateLookupTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new RateLookup()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['RATELOOKUPID' => 12345],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "RATELOOKUPID" with value 12345 is expected to be of type "string", but is of '
                    . 'type "int".',
                ],
            ],
            'valid' => [['RATELOOKUPID' => 'A10A9A919311'], ['RATELOOKUPID' => 'A10A9A919311']],
        ];
    }
}
