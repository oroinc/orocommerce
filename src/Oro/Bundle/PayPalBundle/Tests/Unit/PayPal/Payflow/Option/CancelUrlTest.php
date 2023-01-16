<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\CancelUrl;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class CancelUrlTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new CancelUrl(false)];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid url' => [
                ['CANCELURL' => 123],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "CANCELURL" with value 123 is expected to be of type "string", but is of '
                    . 'type "int".',
                ],
            ],
            'valid' => [['CANCELURL' => 'http://127.0.0.1'], ['CANCELURL' => 'http://127.0.0.1']],
        ];
    }
}
