<?php

namespace Oro\Bundle\PaymentBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

/**
 * Provides payment status of passed entity according to its transactions.
 */
class PaymentStatusProvider implements PaymentStatusProviderInterface
{
    const FULL = 'full';
    const PARTIALLY = 'partially';
    const INVOICED = 'invoiced';
    const AUTHORIZED = 'authorized';
    const DECLINED = 'declined';
    const PENDING = 'pending';
    const CANCELED = 'canceled';

    /** @var PaymentTransactionProvider */
    protected $paymentTransactionProvider;

    /** @var TotalProcessorProvider */
    protected $totalProcessorProvider;

    public function __construct(
        PaymentTransactionProvider $paymentTransactionProvider,
        TotalProcessorProvider $totalProcessorProvider
    ) {
        $this->paymentTransactionProvider = $paymentTransactionProvider;
        $this->totalProcessorProvider = $totalProcessorProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentStatus($entity)
    {
        $paymentTransactions = new ArrayCollection($this->paymentTransactionProvider->getPaymentTransactions($entity));

        return $this->getStatusByEntityAndTransactions($entity, $paymentTransactions);
    }

    /**
     * @param object $entity
     * @param ArrayCollection $paymentTransactions
     * @return string
     */
    private function getStatusByEntityAndTransactions($entity, ArrayCollection $paymentTransactions)
    {
        if ($this->hasCanceledTransactions($paymentTransactions)) {
            return self::CANCELED;
        }

        $total = $this->totalProcessorProvider->getTotal($entity);

        if ($this->hasSuccessfulTransactions($paymentTransactions, $total)) {
            return self::FULL;
        }

        if ($this->hasPartialTransactions($paymentTransactions, $total)) {
            return self::PARTIALLY;
        }

        if ($this->hasInvoiceTransactions($paymentTransactions)) {
            return self::INVOICED;
        }

        if ($this->hasAuthorizeTransactions($paymentTransactions)) {
            return self::AUTHORIZED;
        }

        if ($this->hasDeclinedTransactions($paymentTransactions)) {
            return self::DECLINED;
        }

        return self::PENDING;
    }

    /**
     * @param ArrayCollection $paymentTransactions
     * @return ArrayCollection
     */
    protected function getSuccessfulTransactions(ArrayCollection $paymentTransactions)
    {
        return $paymentTransactions
            ->filter(
                function (PaymentTransaction $paymentTransaction) {
                    return $paymentTransaction->isSuccessful()
                    && in_array(
                        $paymentTransaction->getAction(),
                        [
                            PaymentMethodInterface::CAPTURE,
                            PaymentMethodInterface::CHARGE,
                            PaymentMethodInterface::PURCHASE,
                        ],
                        true
                    );
                }
            );
    }

    /**
     * @param ArrayCollection $paymentTransactions
     * @return float
     */
    protected function getTransactionAmounts(ArrayCollection $paymentTransactions)
    {
        $amounts = $paymentTransactions->map(function (PaymentTransaction $paymentTransaction) {
            return $paymentTransaction->getAmount();
        });

        return array_sum($amounts->toArray());
    }

    /**
     * @param ArrayCollection $paymentTransactions
     * @param Subtotal $total
     * @return bool
     */
    protected function hasSuccessfulTransactions(ArrayCollection $paymentTransactions, Subtotal $total)
    {
        $successfulTransactions = $this->getSuccessfulTransactions($paymentTransactions);
        $transactionAmount = $this->getTransactionAmounts($successfulTransactions);

        return $successfulTransactions->count() && $transactionAmount >= $total->getAmount();
    }

    /**
     * @param ArrayCollection $paymentTransactions
     * @param Subtotal $total
     * @return bool
     */
    protected function hasPartialTransactions(ArrayCollection $paymentTransactions, Subtotal $total)
    {
        $successfulTransactions = $this->getSuccessfulTransactions($paymentTransactions);
        $transactionAmount = $this->getTransactionAmounts($successfulTransactions);

        return $successfulTransactions->count() && $transactionAmount < $total->getAmount();
    }

    /**
     * @param ArrayCollection $paymentTransactions
     * @return bool
     */
    protected function hasAuthorizeTransactions(ArrayCollection $paymentTransactions)
    {
        return false === $paymentTransactions
            ->filter(
                function (PaymentTransaction $paymentTransaction) {
                    if ($paymentTransaction->isClone()) {
                        return false;
                    }

                    return $paymentTransaction->isActive()
                    && $paymentTransaction->isSuccessful()
                    && $paymentTransaction->getAction() === PaymentMethodInterface::AUTHORIZE;
                }
            )
            ->isEmpty();
    }

    /**
     * @param ArrayCollection $paymentTransactions
     * @return bool
     */
    protected function hasDeclinedTransactions(ArrayCollection $paymentTransactions)
    {
        return $paymentTransactions->count() > 0 && $paymentTransactions
            ->filter(
                function (PaymentTransaction $paymentTransaction) {
                    return !$paymentTransaction->isSuccessful() && !$paymentTransaction->isActive();
                }
            )->count() === $paymentTransactions->count();
    }

    /**
     * @param ArrayCollection $paymentTransactions
     * @return bool
     */
    protected function hasInvoiceTransactions(ArrayCollection $paymentTransactions)
    {
        return false === $paymentTransactions
                ->filter(
                    function (PaymentTransaction $paymentTransaction) {
                        if ($paymentTransaction->isClone()) {
                            return false;
                        }

                        return $paymentTransaction->isActive()
                            && $paymentTransaction->isSuccessful()
                            && $paymentTransaction->getAction() === PaymentMethodInterface::INVOICE;
                    }
                )
                ->isEmpty();
    }

    /**
     * @param ArrayCollection $paymentTransactions
     *
     * @return bool
     */
    protected function hasCanceledTransactions(ArrayCollection $paymentTransactions)
    {
        return false === $paymentTransactions
                ->filter(
                    function (PaymentTransaction $paymentTransaction) {
                        if ($paymentTransaction->isClone()) {
                            return false;
                        }

                        return $paymentTransaction->isSuccessful()
                            && $paymentTransaction->getAction() === PaymentMethodInterface::CANCEL;
                    }
                )
                ->isEmpty();
    }
}
