<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option\ExpirationDate;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;

class ExpirationDateTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new ExpirationDate()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['EXPDATE' => 1459343942],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "EXPDATE" with value 1459343942 is expected to be of type "string", but is of ' .
                    'type "integer".',
                ],
            ],
            'valid' => [['EXPDATE' => '0218'], ['EXPDATE' => '0218']],
        ];
    }
}
