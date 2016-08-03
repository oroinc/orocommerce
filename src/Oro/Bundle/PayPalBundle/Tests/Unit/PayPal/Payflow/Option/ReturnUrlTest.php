<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\ReturnUrl;

class ReturnUrlTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new ReturnUrl(false)];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],

            'invalid url' => [
                ['RETURNURL' => 123],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "RETURNURL" with value 123 is expected to be of type "string", but is of ' .
                    'type "integer".',
                ],
            ],
            'invalid type' => [
                ['RETURNURL' => new \stdClass()],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "RETURNURL" with value stdClass is expected to be of type "string", but is of ' .
                    'type "stdClass".',
                ],
            ],
            'valid' => [['RETURNURL' => 'http://127.0.0.1'], ['RETURNURL' => 'http://127.0.0.1']],
        ];
    }
}
