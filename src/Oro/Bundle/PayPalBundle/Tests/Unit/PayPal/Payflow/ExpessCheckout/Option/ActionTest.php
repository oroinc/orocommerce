<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\ExpressCheckout\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;

class ActionTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new ECOption\Action()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['ACTION' => 'some'],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
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
