<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;

class CancelUrlTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new ECOption\CancelUrl(), new ECOption\Action()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid url' => [
                ['CANCELURL' => 123, ECOption\Action::ACTION => ECOption\Action::SET_EC],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "CANCELURL" with value 123 is expected to be of type "string", but is of ' .
                    'type "integer".',
                ],
            ],
            'not allowed for non SET_EC' => [
                ['CANCELURL' => 'http://127.0.0.1', ECOption\Action::ACTION => ECOption\Action::DO_EC],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException',
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
