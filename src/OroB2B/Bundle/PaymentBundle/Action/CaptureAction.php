<?php

namespace OroB2B\Bundle\PaymentBundle\Action;

use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class CaptureAction extends AbstractPaymentMethodAction
{
    /** {@inheritdoc} */
    protected function executeAction($context)
    {
        $options = $this->getOptions($context);

        $paymentTransaction = $this->paymentTransactionProvider->getActiveAuthorizePaymentTransaction(
            $options['object'],
            $options['amount'],
            $options['currency']
        );

        if (!$paymentTransaction) {
            return;
        }

        $capturePaymentTransaction = $this->paymentTransactionProvider->createPaymentTransaction(
            $paymentTransaction->getPaymentMethod(),
            PaymentMethodInterface::CAPTURE,
            $options['object']
        );

        $capturePaymentTransaction
            ->setAmount($options['amount'])
            ->setCurrency($options['currency'])
            ->setSourcePaymentTransaction($paymentTransaction);

        $this->paymentMethodRegistry
            ->getPaymentMethod($capturePaymentTransaction->getPaymentMethod())
            ->execute($capturePaymentTransaction);

        $this->paymentTransactionProvider->savePaymentTransaction($capturePaymentTransaction);
        $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);
    }
}
