<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Option;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\RateLookup;

class RateLookupTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOption()
    {
        return new RateLookup();
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['RATELOOKUPID' => 12345],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "RATELOOKUPID" with value 12345 is expected to be of type "string", but is of ' .
                    'type "integer".',
                ],
            ],
            'valid' => [['RATELOOKUPID' => 'A10A9A919311'], ['RATELOOKUPID' => 'A10A9A919311']],
        ];
    }
}
