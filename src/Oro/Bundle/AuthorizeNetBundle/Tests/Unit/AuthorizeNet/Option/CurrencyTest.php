<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Option;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class CurrencyTest extends AbstractOptionTest
{
    /** {@inheritdoc} */
    protected function getOptions()
    {
        return [new Option\Currency()];
    }

    /** {@inheritdoc} */
    public function configureOptionDataProvider()
    {
        return [
            'invalid type' => [
                ['Currency' => 'UAH'],
                [],
                [
                    'Symfony\Component\OptionsResolver\Exception\InvalidOptionsException',
                    'The option "Currency" with value "UAH" is invalid. Accepted values are: "AUD", "USD", "CAD", '.
                    '"EUR", "GBP", "NZD".',
                ],
            ],
            'valid' => [
                ['Currency' => 'USD'],
                ['Currency' => 'USD'],
            ],
        ];
    }

    /**
     * @dataProvider requiredCurrencyPaymentActionProvider
     * @param string $paymentAction
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required option "Currency" is missing.
     */
    public function testRequired($paymentAction)
    {
        $currency = new Option\Currency();
        $transaction = new Option\Transaction();
        $resolver = new Option\OptionsResolver();

        $resolver
            ->addOption($currency)
            ->addOption($transaction);

        $resolver->resolve(['transaction_type' => $paymentAction]);
    }

    public function testNotRequired()
    {
        $currency = new Option\Currency();
        $transaction = new Option\Transaction();
        $resolver = new Option\OptionsResolver();

        $resolver
            ->addOption($currency)
            ->addOption($transaction);

        $resolver->resolve(['transaction_type' => 'priorAuthCaptureTransaction']);
    }

    /**
     * @return array
     */
    public function requiredCurrencyPaymentActionProvider()
    {
        return [
            ['authCaptureTransaction'],
            ['authOnlyTransaction'],
        ];
    }
}
