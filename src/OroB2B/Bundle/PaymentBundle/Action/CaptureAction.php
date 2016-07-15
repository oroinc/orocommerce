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
            $options['currency'],
            $options['paymentMethod']
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

        if (!empty($options['transactionOptions'])) {
            $capturePaymentTransaction->setTransactionOptions($options['transactionOptions']);
        }

        $response = $this->executePaymentTransaction($capturePaymentTransaction);

        $this->paymentTransactionProvider->savePaymentTransaction($capturePaymentTransaction);
        $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);

        $this->setAttributeValue(
            $context,
            array_merge(
                [
                    'transaction' => $capturePaymentTransaction->getEntityIdentifier(),
                    'successful' => $capturePaymentTransaction->isSuccessful(),
                    'message' => null,
                ],
                (array) $capturePaymentTransaction->getTransactionOptions(),
                $response
            )
        );
    }
}
