<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Action;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Action to refund payments partially.
 */
class PaymentTransactionPartialRefundAction extends PaymentTransactionRefundAction
{
    #[\Override]
    protected function configureOptionsResolver(OptionsResolver $resolver)
    {
        parent::configureOptionsResolver($resolver);
        $resolver->setRequired('amount');
    }

    #[\Override]
    protected function configureValuesResolver(OptionsResolver $resolver)
    {
        parent::configureValuesResolver($resolver);
        $resolver->setRequired('amount');
    }

    #[\Override]
    protected function createTransaction(PaymentTransaction $sourceTransaction, array $options): PaymentTransaction
    {
        $refundPaymentTransaction = parent::createTransaction($sourceTransaction, $options);
        $refundPaymentTransaction->setAmount($options['amount']);

        return $refundPaymentTransaction;
    }
}
