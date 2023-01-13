<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\ExpessCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class CancelUrlTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new ECOption\CancelUrl(), new ECOption\Action()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid url' => [
                ['CANCELURL' => 123, ECOption\Action::ACTION => ECOption\Action::SET_EC],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "CANCELURL" with value 123 is expected to be of type "string", but is of type "int".',
                ],
            ],
            'not allowed for non SET_EC' => [
                ['CANCELURL' => 'http://127.0.0.1', ECOption\Action::ACTION => ECOption\Action::DO_EC],
                [],
                [
                    UndefinedOptionsException::class,
                    'The option "CANCELURL" does not exist. Defined options are: "ACTION".'
                ]
            ],
            'valid' => [
                ['CANCELURL' => 'http://127.0.0.1', ECOption\Action::ACTION => ECOption\Action::SET_EC],
                ['CANCELURL' => 'http://127.0.0.1', ECOption\Action::ACTION => ECOption\Action::SET_EC]
            ],
        ];
    }
}
