<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option\SilentPost;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class SilentPostTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new SilentPost()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['SILENTPOSTURL' => new \stdClass()],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "SILENTPOSTURL" with value stdClass is expected to be of type "string", but is of '
                    . 'type "stdClass".',
                ],
            ],
            'valid' => [
                ['SILENTPOSTURL' => 'https://localhost/notify'],
                ['SILENTPOSTURL' => 'https://localhost/notify'],
            ],
        ];
    }
}
