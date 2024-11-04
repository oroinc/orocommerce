<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OriginalTransaction;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class OriginalTransactionTest extends AbstractOptionTest
{
    #[\Override]
    protected function getOptions(): array
    {
        return [new OriginalTransaction()];
    }

    #[\Override]
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['ORIGID' => 12345],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "ORIGID" with value 12345 is expected to be of type "string", but is of '
                    . 'type "int".',
                ],
            ],
            'valid' => [['ORIGID' => 'A10A9A919311'], ['ORIGID' => 'A10A9A919311']],
        ];
    }
}
