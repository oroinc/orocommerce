<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\ExpessCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class ActionTest extends AbstractOptionTest
{
    /**
     * {@inheritDoc}
     */
    protected function getOptions(): array
    {
        return [new ECOption\Action()];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptionDataProvider(): array
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['ACTION' => 'some'],
                [],
                [
                    InvalidOptionsException::class,
                    'The option "ACTION" with value "some" is invalid. Accepted values are: "S", "G", "D".',
                ],
            ],
            'valid SET_EC' => [
                ['ACTION' => 'S'],
                ['ACTION' => 'S'],
            ],
            'valid DO_EC' => [
                ['ACTION' => 'D'],
                ['ACTION' => 'D'],
            ],
            'valid GET_EC_DETAILS' => [
                ['ACTION' => 'G'],
                ['ACTION' => 'G'],
            ],
        ];
    }
}
