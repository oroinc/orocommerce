<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option\Code;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class CodeTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new Code()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['CVV2' => 123],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "CVV2" with value 123 is expected to be of type "string", but is of type "int".',
                ],
            ],
            'valid' => [['CVV2' => '123'], ['CVV2' => '123']],
        ];
    }
}
