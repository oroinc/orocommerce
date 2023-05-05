<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\ExpessCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class ReturnUrlTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new ECOption\ReturnUrl(), new ECOption\Action()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid url' => [
                ['RETURNURL' => 123, ECOption\Action::ACTION => ECOption\Action::SET_EC],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "RETURNURL" with value 123 is expected to be of type "string", but is of '
                    . 'type "int".',
                ],
            ],
            'not allowed for non SET_EC' => [
                ['RETURNURL' => 'http://127.0.0.1', ECOption\Action::ACTION => ECOption\Action::DO_EC],
                [],
                [
                    UndefinedOptionsException::class,
                    'The option "RETURNURL" does not exist. Defined options are: "ACTION".'
                ]
            ],
            'valid' => [
                ['RETURNURL' => 'http://127.0.0.1', ECOption\Action::ACTION => ECOption\Action::SET_EC],
                ['RETURNURL' => 'http://127.0.0.1', ECOption\Action::ACTION => ECOption\Action::SET_EC]
            ],
        ];
    }
}
