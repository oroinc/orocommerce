<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option\ErrorUrl;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class ErrorUrlTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new ErrorUrl()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid url' => [
                ['ERRORURL' => 123],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "ERRORURL" with value 123 is expected to be of type "string", but is of type "int".',
                ],
            ],
            'valid' => [['ERRORURL' => 'http://127.0.0.1']],
        ];
    }
}
