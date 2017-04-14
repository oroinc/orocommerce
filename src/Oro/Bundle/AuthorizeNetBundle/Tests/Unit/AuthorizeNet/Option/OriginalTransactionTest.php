<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Option;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class OriginalTransactionTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new Option\OriginalTransaction()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'invalid type' => [
                ['original_transaction' => 123.456],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "original_transaction" with value 123.456 is expected to be of type '.
                    '"integer" or "string", but is of type "double".',
                ],
            ],
            'valid_string' => [
                ['original_transaction' => "1"],
                ['original_transaction' => "1"],
            ],
            'valid_integer' => [
                ['original_transaction' => 1],
                ['original_transaction' => 1],
            ],
        ];
    }

    /**
     * @param string $paymentAction
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required option "original_transaction" is missing.
     */
    public function testRequired()
    {
        $currency = new Option\OriginalTransaction();
        $transaction = new Option\Transaction();
        $resolver = new Option\OptionsResolver();

        $resolver
            ->addOption($currency)
            ->addOption($transaction);

        $resolver->resolve(['transaction_type' => 'priorAuthCaptureTransaction']);
    }

    /**
     * @dataProvider notRequiredOrigTransPaymentActionProvider
     * @param string $paymentAction
     */
    public function testNotRequired($paymentAction)
    {
        $currency = new Option\OriginalTransaction();
        $transaction = new Option\Transaction();
        $resolver = new Option\OptionsResolver();

        $resolver
            ->addOption($currency)
            ->addOption($transaction);

        $resolver->resolve(['transaction_type' => $paymentAction]);
    }

    /**
     * @return array
     */
    public function notRequiredOrigTransPaymentActionProvider()
    {
        return [
            ['authCaptureTransaction'],
            ['authOnlyTransaction'],
        ];
    }
}
