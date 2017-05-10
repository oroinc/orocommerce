<?php

namespace Oro\Bundle\ApruveBundle\PaymentTransaction\Action;

use Oro\Bundle\ApruveBundle\Method\ApruvePaymentMethod;
use Oro\Bundle\PaymentBundle\Action\AbstractPaymentMethodAction;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Replacement of regular PaymentTransactionCaptureAction for Apruve.
 * Creates and initiates execution of transactions for Apruve Invoice and Apruve Shipment.
 */
class PaymentTransactionInvoiceAction extends AbstractPaymentMethodAction
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
        $invoicePaymentTransaction = $this
            ->createPaymentTransaction($authorizePaymentTransaction, ApruvePaymentMethod::INVOICE, $options);

        $attributeValue = $this->executeActionOnTransaction($invoicePaymentTransaction);

        // Proceed to shipment if invoice transaction is successful.
        if ($invoicePaymentTransaction->isSuccessful()) {
            $shipmentPaymentTransaction = $this
                ->createPaymentTransaction($invoicePaymentTransaction, ApruvePaymentMethod::SHIPMENT, $options);

            // Execute shipment transaction and merge result with invoice transaction result.
            $attributeValue = $this->executeActionOnTransaction($shipmentPaymentTransaction) + $attributeValue;

            // Provide invoice transaction id just in case if somebody needs it later.
            $attributeValue['invoiceTransaction'] = $invoicePaymentTransaction->getId();
        }

        $this->setAttributeValue($context, $attributeValue);
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     *
     * @return array
     */
    private function executeActionOnTransaction(PaymentTransaction $paymentTransaction)
    {
        $response = $this->executePaymentTransaction($paymentTransaction);

        $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);

        return array_merge(
            [
                'transaction' => $paymentTransaction->getId(),
                'successful' => $paymentTransaction->isSuccessful(),
                'message' => null,
            ],
            (array)$paymentTransaction->getTransactionOptions(),
            $response
        );
    }

    /**
     * @param PaymentTransaction $sourcePaymentTransaction
     * @param string             $action
     * @param array              $options Options from context.
     *
     * @return PaymentTransaction
     */
    private function createPaymentTransaction(PaymentTransaction $sourcePaymentTransaction, $action, array $options)
    {
        $paymentTransaction = $this->paymentTransactionProvider
            ->createPaymentTransactionByParentTransaction($action, $sourcePaymentTransaction);

        if (!empty($options['transactionOptions'])) {
            $paymentTransaction->setTransactionOptions($options['transactionOptions']);
        }

        return $paymentTransaction;
    }
}
