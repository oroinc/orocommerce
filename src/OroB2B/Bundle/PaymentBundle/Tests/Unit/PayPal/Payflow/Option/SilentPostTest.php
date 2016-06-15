<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Option;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\SilentPost;

class SilentPostTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOption()
    {
        return new SilentPost();
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['SILENTPOSTURL' => new \stdClass()],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "SILENTPOSTURL" with value stdClass is expected to be of type "string", but is of ' .
                    'type "stdClass".',
                ],
            ],
            'valid' => [
                ['SILENTPOSTURL' => 'https://localhost/notify'],
                ['SILENTPOSTURL' => 'https://localhost/notify'],
            ],
        ];
    }
}
