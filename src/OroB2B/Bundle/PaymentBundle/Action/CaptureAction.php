<?php

namespace OroB2B\Bundle\PaymentBundle\Action;

use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use OroB2B\Bundle\PaymentBundle\Model\AmountAwareInterface;

class CaptureAction extends AbstractPaymentMethodAction
{

    /** {@inheritdoc} */
    protected function executeAction($context)
    {
        $object = $this->contextAccessor->getValue($context, $this->options['object']);
        if (!$object instanceof AmountAwareInterface) {
            return;
        }

        $paymentTransaction = $this->paymentTransactionProvider->getActivePaymentTransaction(
            $object,
            PaymentMethodInterface::AUTHORIZE
        );

        if (!$paymentTransaction) {
            return;
        }

        $capturePaymentTransaction = $this->paymentTransactionProvider->createPaymentTransaction(
            $paymentTransaction->getPaymentMethod(),
            PaymentMethodInterface::CAPTURE,
            $object
        );

        $capturePaymentTransaction
            ->setAmount($object->getAmount())
            ->setCurrency($object->getCurrency())
            ->setSourcePaymentTransaction($paymentTransaction);

        $this->paymentMethodRegistry
            ->getPaymentMethod($capturePaymentTransaction->getPaymentMethod())
            ->execute($capturePaymentTransaction);

        $this->paymentTransactionProvider->savePaymentTransaction($capturePaymentTransaction);
        $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);
    }
}
