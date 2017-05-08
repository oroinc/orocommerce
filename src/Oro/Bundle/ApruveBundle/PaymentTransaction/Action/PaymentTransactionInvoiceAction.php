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
        list($invoicePaymentTransaction, $invoiceResult) = $this
            ->executeSpecificAction($authorizePaymentTransaction, ApruvePaymentMethod::INVOICE, $options);

        list($shipmentPaymentTransaction, $shipmentResult) = $this
            ->executeSpecificAction($invoicePaymentTransaction, ApruvePaymentMethod::SHIPMENT, $options);

        $this->setAttributeValue(
            $context,
            array_merge(
                [
                    'invoiceTransaction' => $invoicePaymentTransaction->getEntityIdentifier(),
                    'shipmentTransaction' => $shipmentPaymentTransaction->getEntityIdentifier(),
                    // We consider shipment transaction as a resulting transaction, as it indicates
                    // the result of both invoice and shipment creations, because without successful
                    // invoice transaction it would be impossible to execute shipment.
                    'transaction' => $shipmentPaymentTransaction->getEntityIdentifier(),
                    'successful' => $shipmentPaymentTransaction->isSuccessful(),
                    'message' => null,
                ],
                (array)$invoicePaymentTransaction->getTransactionOptions(),
                (array)$shipmentPaymentTransaction->getTransactionOptions(),
                $invoiceResult,
                $shipmentResult
            )
        );
    }

    /**
     * @param PaymentTransaction $sourcePaymentTransaction
     * @param string             $actionName
     * @param array              $options
     *
     * @return array
     */
    private function executeSpecificAction(PaymentTransaction $sourcePaymentTransaction, $actionName, array $options)
    {
        $paymentTransaction = $this->paymentTransactionProvider
            ->createPaymentTransactionByParentTransaction($actionName, $sourcePaymentTransaction);

        if (!empty($options['transactionOptions'])) {
            $paymentTransaction->setTransactionOptions($options['transactionOptions']);
        }

        $response = $this->executePaymentTransaction($paymentTransaction);

        $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);
        $this->paymentTransactionProvider->savePaymentTransaction($sourcePaymentTransaction);

        return [$paymentTransaction, $response];
    }
}
