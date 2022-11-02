<?php

namespace Oro\Bundle\PaymentBundle\Action;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\Action\CaptureActionInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Action to capture payments using capture payment transactions
 */
class PaymentTransactionCaptureAction extends AbstractPaymentMethodAction
{
    const OPTION_PAYMENT_TRANSACTION = 'paymentTransaction';

    protected function configureOptionsResolver(OptionsResolver $resolver)
    {
        parent::configureOptionsResolver($resolver);

        $resolver
            ->remove(['object', 'amount', 'currency', 'paymentMethod'])
            ->setRequired([self::OPTION_PAYMENT_TRANSACTION])
            ->addAllowedTypes(
                self::OPTION_PAYMENT_TRANSACTION,
                [PaymentTransaction::class, PropertyPathInterface::class]
            );
    }

    protected function configureValuesResolver(OptionsResolver $resolver)
    {
        parent::configureValuesResolver($resolver);

        $resolver
            ->remove(['object', 'amount', 'currency', 'paymentMethod'])
            ->setRequired([self::OPTION_PAYMENT_TRANSACTION])
            ->addAllowedTypes(self::OPTION_PAYMENT_TRANSACTION, PaymentTransaction::class);
    }

    /**
     * @param array $options
     *
     * @return PaymentTransaction
     */
    private function extractPaymentTransactionFromOptions(array $options)
    {
        return $options[self::OPTION_PAYMENT_TRANSACTION];
    }

    /**
     * {@inheritDoc}
     */
    protected function executeAction($context)
    {
        $options = $this->getOptions($context);

        $authorizePaymentTransaction = $this->extractPaymentTransactionFromOptions($options);
        if (!$this->paymentMethodProvider->hasPaymentMethod($authorizePaymentTransaction->getPaymentMethod())) {
            $this->setAttributeValue(
                $context,
                array_merge(
                    [
                        'transaction' => $authorizePaymentTransaction->getId(),
                        'successful' => false,
                        'message' => 'oro.payment.message.error',
                    ],
                    $options['transactionOptions']
                )
            );

            return;
        }

        $paymentMethod = $this->paymentMethodProvider
            ->getPaymentMethod($authorizePaymentTransaction->getPaymentMethod());

        if ($paymentMethod instanceof CaptureActionInterface && $paymentMethod->useSourcePaymentTransaction()) {
            $capturePaymentTransaction = $authorizePaymentTransaction;
            $capturePaymentTransaction->setAction(PaymentMethodInterface::CAPTURE);
        } else {
            $capturePaymentTransaction = $this->paymentTransactionProvider->createPaymentTransactionByParentTransaction(
                PaymentMethodInterface::CAPTURE,
                $authorizePaymentTransaction
            );
        }

        if (!empty($options['transactionOptions'])) {
            $capturePaymentTransaction->setTransactionOptions($options['transactionOptions']);
        }

        $response = $this->executePaymentTransaction($capturePaymentTransaction);

        $this->paymentTransactionProvider->savePaymentTransaction($capturePaymentTransaction);
        $this->paymentTransactionProvider->savePaymentTransaction($authorizePaymentTransaction);

        $this->setAttributeValue(
            $context,
            array_merge(
                [
                    'transaction' => $capturePaymentTransaction->getId(),
                    'successful' => $capturePaymentTransaction->isSuccessful(),
                    'message' => $capturePaymentTransaction->isSuccessful() ? null : 'oro.payment.message.error',
                ],
                $capturePaymentTransaction->getTransactionOptions(),
                $response
            )
        );
    }
}
