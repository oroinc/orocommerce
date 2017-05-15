<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Option;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class TransactionTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new Option\Transaction()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'required' => [
                [],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\MissingOptionsException',
                    'The required option "transaction_type" is missing.',
                ],
            ],
            'invalid_value' => [
                ['transaction_type' => 12345],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "transaction_type" with value 12345 is invalid. Accepted values are: '.
                    '"authOnlyTransaction", "priorAuthCaptureTransaction", "authCaptureTransaction"',
                ],
            ],
        ];
    }

    /**
     * @dataProvider validTransactionValuesDataProvider
     * @param string $transactionAction
     */
    public function testValidTransactionValues($transactionAction)
    {
        $transaction = new Option\Transaction();
        $resolver = new Option\OptionsResolver();

        $resolver->addOption($transaction);
        $resolver->resolve(['transaction_type' => $transactionAction]);
    }

    public function validTransactionValuesDataProvider()
    {
        return [
            ['authOnlyTransaction'],
            ['priorAuthCaptureTransaction'],
            ['authCaptureTransaction'],
        ];
    }
}
