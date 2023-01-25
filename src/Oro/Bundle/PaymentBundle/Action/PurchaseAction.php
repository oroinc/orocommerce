<?php

namespace Oro\Bundle\PaymentBundle\Action;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProviderInterface;

/**
 * Create payment transaction and execute purchase.
 */
class PurchaseAction extends AbstractPaymentMethodAction
{
    const SAVE_FOR_LATER_USE = 'saveForLaterUse';

    private PaymentStatusProviderInterface $paymentStatusProvider;

    public function setPaymentStatusProvider(PaymentStatusProviderInterface $paymentStatusProvider): void
    {
        $this->paymentStatusProvider = $paymentStatusProvider;
    }

    /** {@inheritdoc} */
    protected function executeAction($context)
    {
        $options = $this->getOptions($context);

        $paymentTransaction = $this->paymentTransactionProvider->createPaymentTransaction(
            $options['paymentMethod'],
            PaymentMethodInterface::PURCHASE,
            $options['object']
        );

        $isPaymentMethodSupportsValidation = $this->isPaymentMethodSupportsValidation($paymentTransaction);

        $attributes = [
            'paymentMethod' => $options['paymentMethod'],
            'paymentMethodSupportsValidation' => (bool)$isPaymentMethodSupportsValidation,
        ];

        if ($isPaymentMethodSupportsValidation) {
            $sourcePaymentTransaction = $this->paymentTransactionProvider
                ->getActiveValidatePaymentTransaction($options['paymentMethod']);

            if (!$sourcePaymentTransaction) {
                throw new \RuntimeException('Validation payment transaction not found');
            }

            $paymentTransaction->setSourcePaymentTransaction($sourcePaymentTransaction);
        }

        $paymentTransaction
            ->setAmount($options['amount'])
            ->setCurrency($options['currency']);

        if (!empty($options['transactionOptions'])) {
            $paymentTransaction->setTransactionOptions($options['transactionOptions']);
        }

        $response = $this->executePaymentTransaction($paymentTransaction);

        $this->paymentTransactionProvider->savePaymentTransaction($paymentTransaction);

        if ($isPaymentMethodSupportsValidation) {
            $attributes['purchaseSuccessful'] = $paymentTransaction->isSuccessful();

            $this->handleSaveForLaterUse($paymentTransaction);
        }

        if ($paymentTransaction->isSuccessful() && $this->isPaidPartially($options['object'])) {
            $attributes['purchasePartial'] = true;
        }

        $this->setAttributeValue(
            $context,
            array_merge(
                $attributes,
                $this->getCallbackUrls($paymentTransaction),
                (array) $paymentTransaction->getTransactionOptions(),
                $response
            )
        );
    }

    protected function isPaymentMethodSupportsValidation(PaymentTransaction $paymentTransaction): bool
    {
        $paymentMethodIdentifier = $paymentTransaction->getPaymentMethod();
        if ($this->paymentMethodProvider->hasPaymentMethod($paymentMethodIdentifier)) {
            return $this->paymentMethodProvider
                ->getPaymentMethod($paymentMethodIdentifier)
                ->supports(PaymentMethodInterface::VALIDATE);
        }

        return false;
    }

    protected function handleSaveForLaterUse(PaymentTransaction $paymentTransaction): void
    {
        $sourcePaymentTransaction = $paymentTransaction->getSourcePaymentTransaction();
        $sourcePaymentTransactionOptions = $sourcePaymentTransaction->getTransactionOptions();
        if (empty($sourcePaymentTransactionOptions[self::SAVE_FOR_LATER_USE])) {
            $sourcePaymentTransaction->setActive(false);
            $this->paymentTransactionProvider->savePaymentTransaction($sourcePaymentTransaction);
        }
    }

    private function isPaidPartially(object $object): bool
    {
        return in_array(
            $this->paymentStatusProvider->getPaymentStatus($object),
            [
                PaymentStatusProvider::AUTHORIZED_PARTIALLY,
                PaymentStatusProvider::PARTIALLY
            ],
            true
        );
    }
}
