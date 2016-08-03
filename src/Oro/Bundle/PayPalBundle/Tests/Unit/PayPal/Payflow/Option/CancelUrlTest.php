<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\CancelUrl;

class CancelUrlTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new CancelUrl(false)];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid url' => [
                ['CANCELURL' => 123],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "CANCELURL" with value 123 is expected to be of type "string", but is of ' .
                    'type "integer".',
                ],
            ],
            'valid' => [['CANCELURL' => 'http://127.0.0.1'], ['CANCELURL' => 'http://127.0.0.1']],
        ];
    }
}
