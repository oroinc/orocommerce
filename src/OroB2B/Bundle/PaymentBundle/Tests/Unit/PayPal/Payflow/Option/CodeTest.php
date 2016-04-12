<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Option;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\Code;

class CodeTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOption()
    {
        return new Code();
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [],
            'invalid type' => [
                ['CVV2' => 123],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "CVV2" with value 123 is expected to be of type "string", but is of ' .
                    'type "integer".',
                ],
            ],
            'valid' => [['CVV2' => '123'], ['CVV2' => '123']],
        ];
    }
}
