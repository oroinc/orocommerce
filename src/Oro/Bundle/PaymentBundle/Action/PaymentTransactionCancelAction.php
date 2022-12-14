<?php

namespace Oro\Bundle\PaymentBundle\Action;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Action to cancel payments
 */
class PaymentTransactionCancelAction extends AbstractPaymentMethodAction
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
    protected function extractPaymentTransactionFromOptions(array $options)
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

        $cancelPaymentTransaction = $this->paymentTransactionProvider->createPaymentTransactionByParentTransaction(
            PaymentMethodInterface::CANCEL,
            $authorizePaymentTransaction
        );

        if (!empty($options['transactionOptions'])) {
            $cancelPaymentTransaction->setTransactionOptions($options['transactionOptions']);
        }

        $response = $this->executePaymentTransaction($cancelPaymentTransaction);

        if ($cancelPaymentTransaction->isSuccessful()) {
            $authorizePaymentTransaction->setActive(false);
        }
        $this->paymentTransactionProvider->savePaymentTransaction($cancelPaymentTransaction);
        $this->paymentTransactionProvider->savePaymentTransaction($authorizePaymentTransaction);

        $this->setAttributeValue(
            $context,
            array_merge(
                [
                    'transaction' => $cancelPaymentTransaction->getId(),
                    'successful' => $cancelPaymentTransaction->isSuccessful(),
                    'message' => $cancelPaymentTransaction->isSuccessful() ? null : 'oro.payment.message.error',
                ],
                $cancelPaymentTransaction->getTransactionOptions(),
                $response
            )
        );
    }
}
