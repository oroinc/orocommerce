<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\ExpessCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option\Action;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option\Token;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class TokenTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new Token(), new Action()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['TOKEN' => 12345, Action::ACTION => Action::GET_EC_DETAILS],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "TOKEN" with value 12345 is expected to be of type "string", but is of type "int".',
                ],
            ],
            'not passed token with GET_EC_DETAILS (required)' => [
                [Action::ACTION => Action::GET_EC_DETAILS],
                [],
                [
                    MissingOptionsException::class,
                    'The required option "TOKEN" is missing.',
                ],
            ],
            'not passed token with DO_EC (required)' => [
                [Action::ACTION => Action::DO_EC],
                [],
                [
                    MissingOptionsException::class,
                    'The required option "TOKEN" is missing.',
                ],
            ],
            'valid with GET_EC_DETAILS (not required option)' => [
                ['TOKEN' => 'EC-72950149M21072354', Action::ACTION => Action::SET_EC],
                ['TOKEN' => 'EC-72950149M21072354', Action::ACTION => Action::SET_EC],
            ],
            'not passed with GET_EC_DETAILS (not required option)' => [
                [Action::ACTION => Action::SET_EC],
                [Action::ACTION => Action::SET_EC],
            ],
            'valid with GET_EC_DETAILS (required option)' => [
                ['TOKEN' => 'EC-72950149M21072354', Action::ACTION => Action::GET_EC_DETAILS],
                ['TOKEN' => 'EC-72950149M21072354', Action::ACTION => Action::GET_EC_DETAILS],
            ],
            'valid with DO_EC (required option)' => [
                ['TOKEN' => 'EC-72950149M21072354', Action::ACTION => Action::DO_EC],
                ['TOKEN' => 'EC-72950149M21072354', Action::ACTION => Action::DO_EC],
            ],
        ];
    }
}
