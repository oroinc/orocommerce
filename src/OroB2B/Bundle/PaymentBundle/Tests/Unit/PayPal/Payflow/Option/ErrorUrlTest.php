<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Option;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\ErrorUrl;

class ErrorUrlTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOption()
    {
        return new ErrorUrl();
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['ERRORURL' => new \stdClass()],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "ERRORURL" with value stdClass is expected to be of type "string", but is of ' .
                    'type "stdClass".',
                ],
            ],
            'valid' => [
                ['ERRORURL' => 'https://localhost/error'],
                ['ERRORURL' => 'https://localhost/error'],
            ],
        ];
    }
}
