<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\ReturnUrl;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class ReturnUrlTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new ReturnUrl(false)];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],

            'invalid url' => [
                ['RETURNURL' => 123],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "RETURNURL" with value 123 is expected to be of type "string", but is of '
                    . 'type "int".',
                ],
            ],
            'invalid type' => [
                ['RETURNURL' => new \stdClass()],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "RETURNURL" with value stdClass is expected to be of type "string", but is of '
                    . 'type "stdClass".',
                ],
            ],
            'valid' => [['RETURNURL' => 'http://127.0.0.1'], ['RETURNURL' => 'http://127.0.0.1']],
        ];
    }
}
