<?php

namespace Oro\Bundle\PaymentBundle\Action;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class PaymentTransactionCaptureAction extends AbstractPaymentMethodAction
{
    const OPTION_PAYMENT_TRANSACTION = 'paymentTransaction';

    /**
     * @param OptionsResolver $resolver
     */
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

    /**
     * @param OptionsResolver $resolver
     */
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

        $capturePaymentTransaction = $this->paymentTransactionProvider->createPaymentTransactionByParentTransaction(
            PaymentMethodInterface::CAPTURE,
            $authorizePaymentTransaction
        );

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
                    'transaction' => $capturePaymentTransaction->getEntityIdentifier(),
                    'successful' => $capturePaymentTransaction->isSuccessful(),
                    'message' => null,
                ],
                (array)$capturePaymentTransaction->getTransactionOptions(),
                $response
            )
        );
    }
}
