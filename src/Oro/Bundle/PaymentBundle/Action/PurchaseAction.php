<?php

namespace Oro\Bundle\PaymentBundle\Action;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Create payment transaction and execute purchase.
 */
class PurchaseAction extends AbstractPaymentMethodAction
{
    public const SAVE_FOR_LATER_USE = 'saveForLaterUse';

    private ?PaymentStatusManager $paymentStatusManager = null;

    public function setPaymentStatusManager(?PaymentStatusManager $paymentStatusManager): void
    {
        $this->paymentStatusManager = $paymentStatusManager;
    }

    #[\Override]
    protected function configureOptionsResolver(OptionsResolver $resolver): void
    {
        parent::configureOptionsResolver($resolver);

        $resolver
            ->setRequired('paymentAction')
            ->setAllowedTypes('paymentAction', ['string', PropertyPathInterface::class])
            ->setDefault('paymentAction', PaymentMethodInterface::PURCHASE);
    }

    #[\Override]
    protected function configureValuesResolver(OptionsResolver $resolver): void
    {
        parent::configureValuesResolver($resolver);

        $resolver
            ->setRequired('paymentAction')
            ->setAllowedTypes('paymentAction', 'string')
            ->setDefault('paymentAction', PaymentMethodInterface::PURCHASE);
    }

    #[\Override]
    protected function executeAction($context)
    {
        $options = $this->getOptions($context);
        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = $this->extractPaymentMethodFromOptions($options);

        $paymentTransaction = $this->paymentTransactionProvider->createPaymentTransaction(
            $paymentMethod->getIdentifier(),
            $options['paymentAction'],
            $options['object']
        );

        $isPaymentMethodSupportsValidation = $this->isPaymentMethodSupportsValidation(
            $paymentTransaction,
            $paymentMethod
        );

        $attributes = [
            'paymentMethod' => $paymentMethod->getIdentifier(),
            'paymentMethodSupportsValidation' => (bool)$isPaymentMethodSupportsValidation,
        ];

        if ($isPaymentMethodSupportsValidation) {
            $sourcePaymentTransaction = $this->paymentTransactionProvider
                ->getActiveValidatePaymentTransaction($paymentMethod->getIdentifier());

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

        $response = $this->executePaymentTransaction($paymentTransaction, $paymentMethod);

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

    protected function isPaymentMethodSupportsValidation(
        PaymentTransaction $paymentTransaction,
        ?PaymentMethodInterface $paymentMethod = null
    ): bool {
        if ($paymentMethod === null) {
            $paymentMethodIdentifier = $paymentTransaction->getPaymentMethod();
            if (!$this->paymentMethodProvider->hasPaymentMethod($paymentMethodIdentifier)) {
                return false;
            }

            $paymentMethod = $this->paymentMethodProvider->getPaymentMethod($paymentMethodIdentifier);
        }

        return $paymentMethod->supports(PaymentMethodInterface::VALIDATE);
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
            $this->paymentStatusManager->getPaymentStatus($object),
            [
                PaymentStatuses::AUTHORIZED_PARTIALLY,
                PaymentStatuses::PAID_PARTIALLY
            ],
            true
        );
    }
}
