<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Option;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class AmountTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new Option\Amount()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'invalid type' => [
                ['amount' => 'twenty backs'],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "amount" with value "twenty backs" is expected to be of type "float" or "integer", '.
                    'but is of type "string".',
                ],
            ],
            'valid_float' => [
                ['amount' => 10.00],
                ['amount' => 10.00],
            ],
            'valid_integer' => [
                ['amount' => 10],
                ['amount' => 10],
            ],
        ];
    }

    /**
     * @dataProvider requiredAmountPaymentActionProvider
     * @param string $paymentAction
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required option "amount" is missing.
     */
    public function testRequired($paymentAction)
    {
        $amount = new Option\Amount();
        $transaction = new Option\Transaction();
        $resolver = new Option\OptionsResolver();

        $resolver
            ->addOption($amount)
            ->addOption($transaction);

        $resolver->resolve(['transaction_type' => $paymentAction]);
    }

    public function testNotRequired()
    {
        $amount = new Option\Amount();
        $transaction = new Option\Transaction();
        $resolver = new Option\OptionsResolver();

        $resolver
            ->addOption($amount)
            ->addOption($transaction);

        $resolver->resolve(['transaction_type' => 'priorAuthCaptureTransaction']);
    }

    /**
     * @return array
     */
    public function requiredAmountPaymentActionProvider()
    {
        return [
            ['authCaptureTransaction'],
            ['authOnlyTransaction'],
        ];
    }
}
