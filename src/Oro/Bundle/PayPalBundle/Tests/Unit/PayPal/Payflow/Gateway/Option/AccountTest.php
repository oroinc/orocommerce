<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Gateway\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option\Account;
use Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option\AbstractOptionTest;

class AccountTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new Account()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'empty' => [
                [],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\MissingOptionsException',
                    'The required option "ACCT" is missing.',
                ],
            ],
            'invalid type' => [
                ['ACCT' => 1234567890123456],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "ACCT" with value 1234567890123456 is expected to be of type "string", but is of ' .
                    'type "integer".',
                ],
            ],
            'valid' => [['ACCT' => '4111111111111111'], ['ACCT' => '4111111111111111']],
        ];
    }
}
