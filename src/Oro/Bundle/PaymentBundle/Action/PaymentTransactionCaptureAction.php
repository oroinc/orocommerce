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
    public const OPTION_PAYMENT_TRANSACTION = 'paymentTransaction';

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

    /**
     * @param array $options
     *
     * @return PaymentTransaction
     */
    private function extractPaymentTransactionFromOptions(array $options)
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

        if ($paymentMethod instanceof CaptureActionInterface && $paymentMethod->useSourcePaymentTransaction()) {
            $capturePaymentTransaction = $sourcePaymentTransaction;
            $capturePaymentTransaction->setAction(PaymentMethodInterface::CAPTURE);
        } else {
            $capturePaymentTransaction = $this->paymentTransactionProvider->createPaymentTransactionByParentTransaction(
                PaymentMethodInterface::CAPTURE,
                $sourcePaymentTransaction
            );
        }

        if (!empty($options['transactionOptions'])) {
            $capturePaymentTransaction->setTransactionOptions($options['transactionOptions']);
        }

        $response = $this->executePaymentTransaction($capturePaymentTransaction, $paymentMethod);

        $this->paymentTransactionProvider->savePaymentTransaction($capturePaymentTransaction);
        $this->paymentTransactionProvider->savePaymentTransaction($sourcePaymentTransaction);

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
