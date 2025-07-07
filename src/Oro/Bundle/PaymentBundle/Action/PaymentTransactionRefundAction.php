<?php

namespace Oro\Bundle\PaymentBundle\Action;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Action to refund captured payments
 */
class PaymentTransactionRefundAction extends AbstractPaymentMethodAction
{
    const OPTION_PAYMENT_TRANSACTION = 'paymentTransaction';

    #[\Override]
    protected function configureOptionsResolver(OptionsResolver $resolver)
    {
        parent::configureOptionsResolver($resolver);

        $resolver
            ->remove(['object', 'amount', 'currency'])
            ->setRequired([self::OPTION_PAYMENT_TRANSACTION])
            ->addAllowedTypes(
                self::OPTION_PAYMENT_TRANSACTION,
                [PaymentTransaction::class, PropertyPathInterface::class]
            );
    }

    #[\Override]
    protected function configureValuesResolver(OptionsResolver $resolver)
    {
        parent::configureValuesResolver($resolver);

        $resolver
            ->remove(['object', 'amount', 'currency'])
            ->setRequired([self::OPTION_PAYMENT_TRANSACTION])
            ->addAllowedTypes(self::OPTION_PAYMENT_TRANSACTION, PaymentTransaction::class);
    }

    protected function extractPaymentTransactionFromOptions(array $options): PaymentTransaction
    {
        return $options[self::OPTION_PAYMENT_TRANSACTION];
    }

    #[\Override]
    protected function extractPaymentMethodFromOptions(array $options): ?PaymentMethodInterface
    {
        $paymentMethod = parent::extractPaymentMethodFromOptions($options);
        if ($paymentMethod !== null) {
            return $paymentMethod;
        }

        $sourcePaymentTransaction = $this->extractPaymentTransactionFromOptions($options);
        if ($this->paymentMethodProvider->hasPaymentMethod($sourcePaymentTransaction->getPaymentMethod())) {
            return $this->paymentMethodProvider->getPaymentMethod($sourcePaymentTransaction->getPaymentMethod());
        }

        return null;
    }

    #[\Override]
    protected function executeAction($context)
    {
        $options = $this->getOptions($context);
        $paymentMethod = $this->extractPaymentMethodFromOptions($options);
        $sourcePaymentTransaction = $this->extractPaymentTransactionFromOptions($options);

        if ($paymentMethod === null) {
            $this->setAttributeValue(
                $context,
                array_merge(
                    [
                        'transaction' => $sourcePaymentTransaction->getId(),
                        'successful' => false,
                        'message' => 'oro.payment.message.error',
                    ],
                    $options['transactionOptions']
                )
            );

            return;
        }

        $refundPaymentTransaction = $this->createTransaction($sourcePaymentTransaction, $options);

        $response = $this->executePaymentTransaction($refundPaymentTransaction, $paymentMethod);

        $this->paymentTransactionProvider->savePaymentTransaction($refundPaymentTransaction);
        $this->paymentTransactionProvider->savePaymentTransaction($sourcePaymentTransaction);

        $this->setAttributeValue(
            $context,
            array_merge(
                [
                    'transaction' => $refundPaymentTransaction->getId(),
                    'successful' => $refundPaymentTransaction->isSuccessful(),
                    'message' => $refundPaymentTransaction->isSuccessful() ? null : 'oro.payment.message.error',
                ],
                $refundPaymentTransaction->getTransactionOptions(),
                $response
            )
        );
    }

    protected function createTransaction(PaymentTransaction $sourceTransaction, array $options): PaymentTransaction
    {
        $refundPaymentTransaction = $this->paymentTransactionProvider->createPaymentTransactionByParentTransaction(
            PaymentMethodInterface::REFUND,
            $sourceTransaction
        );

        if (!empty($options['transactionOptions'])) {
            $refundPaymentTransaction->setTransactionOptions($options['transactionOptions']);
        }

        return $refundPaymentTransaction;
    }
}
