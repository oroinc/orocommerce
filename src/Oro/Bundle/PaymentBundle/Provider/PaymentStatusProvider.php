<?php

namespace Oro\Bundle\PaymentBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Component\Math\BigDecimal;
use Oro\Component\Math\RoundingMode;

/**
 * Provides payment status of passed entity according to its transactions.
 */
class PaymentStatusProvider implements PaymentStatusProviderInterface
{
    public const FULL = 'full';
    public const PARTIALLY = 'partially';
    public const INVOICED = 'invoiced';
    public const AUTHORIZED = 'authorized';
    public const AUTHORIZED_PARTIALLY = 'authorized_partially';
    public const DECLINED = 'declined';
    public const PENDING = 'pending';
    public const CANCELED = 'canceled';
    public const CANCELED_PARTIALLY = 'canceled_partially';
    public const REFUNDED = 'refunded';
    public const REFUNDED_PARTIALLY = 'refunded_partially';

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
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @param object $entity
     * @param ArrayCollection $paymentTransactions
     * @return string
     */
    protected function getStatusByEntityAndTransactions($entity, ArrayCollection $paymentTransactions)
    {
        $total = $this->totalProcessorProvider->getTotal($entity);

        if ($this->isRefundedPartially($paymentTransactions, $total)) {
            return self::REFUNDED_PARTIALLY;
        }

        if ($this->hasRefundedTransactions($paymentTransactions)) {
            return self::REFUNDED;
        }

        if ($this->hasSuccessfulTransactions($paymentTransactions, $total)) {
            return self::FULL;
        }

        if ($this->hasInvoiceTransactions($paymentTransactions)) {
            return self::INVOICED;
        }

        if ($this->hasPartialAuthorizationTransactions($paymentTransactions, $total)) {
            return self::AUTHORIZED_PARTIALLY;
        }

        if ($this->isCanceledPartially($paymentTransactions)) {
            return self::CANCELED_PARTIALLY;
        }

        if ($this->hasPartialTransactions($paymentTransactions, $total)) {
            return self::PARTIALLY;
        }

        if ($this->hasAuthorizeTransactions($paymentTransactions)) {
            return self::AUTHORIZED;
        }

        if ($this->hasCanceledTransactions($paymentTransactions)) {
            return self::CANCELED;
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
        $totalAmount = BigDecimal::of($total->getAmount())->toScale(2, RoundingMode::HALF_UP);

        return $successfulTransactions->count()
            && BigDecimal::of($transactionAmount)->isGreaterThanOrEqualTo($totalAmount);
    }

    /**
     * @param ArrayCollection $paymentTransactions
     * @param Subtotal $total
     * @return bool
     */
    protected function hasPartialTransactions(ArrayCollection $paymentTransactions, Subtotal $total)
    {
        $successfulTransactions = $this->getSuccessfulTransactions($paymentTransactions);
        return $this->isPartial($successfulTransactions, $total);
    }

    protected function hasPartialAuthorizationTransactions(ArrayCollection $paymentTransactions, Subtotal $total): bool
    {
        $authorizeTransactions = $this->getAuthorizeTransactions($paymentTransactions);
        return $this->isPartial($authorizeTransactions, $total);
    }

    /**
     * @param ArrayCollection $paymentTransactions
     * @return bool
     */
    protected function hasAuthorizeTransactions(ArrayCollection $paymentTransactions)
    {
        return !$this->getAuthorizeTransactions($paymentTransactions)->isEmpty();
    }

    protected function getAuthorizeTransactions(ArrayCollection $paymentTransactions): ArrayCollection
    {
        return $paymentTransactions
            ->filter(
                function (PaymentTransaction $paymentTransaction) {
                    if ($paymentTransaction->isClone()) {
                        return false;
                    }

                    return $paymentTransaction->isActive()
                        && $paymentTransaction->isSuccessful()
                        && $paymentTransaction->getAction() === PaymentMethodInterface::AUTHORIZE;
                }
            );
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
        $canceledTransactions = $this->getCanceledTransactions($paymentTransactions);

        return $canceledTransactions->count() > 0;
    }

    protected function getCanceledTransactions(ArrayCollection $paymentTransactions)
    {
        return $paymentTransactions
            ->filter(
                function (PaymentTransaction $paymentTransaction) {
                    if ($paymentTransaction->isClone()) {
                        return false;
                    }

                    return $paymentTransaction->isSuccessful()
                        && $paymentTransaction->getAction() === PaymentMethodInterface::CANCEL;
                }
            );
    }

    protected function isCanceledPartially(ArrayCollection $paymentTransactions)
    {
        $canceledTransactions = $this->getCanceledTransactions($paymentTransactions);
        $successfulTransactions = $this->getSuccessfulTransactions($paymentTransactions);

        return $canceledTransactions->count()
            && $successfulTransactions
            && $this->getTransactionAmounts($successfulTransactions) >
            $this->getTransactionAmounts($canceledTransactions);
    }

    protected function getRefundedTransactions(ArrayCollection $paymentTransactions): ArrayCollection
    {
        return $paymentTransactions
            ->filter(
                function (PaymentTransaction $paymentTransaction) {
                    if ($paymentTransaction->isClone()) {
                        return false;
                    }

                    return $paymentTransaction->isSuccessful()
                        && $paymentTransaction->getAction() === PaymentMethodInterface::REFUND;
                }
            );
    }

    protected function hasRefundedTransactions(ArrayCollection $paymentTransactions): bool
    {
        $refundTransactions = $this->getRefundedTransactions($paymentTransactions);
        return (bool) $refundTransactions->count();
    }

    protected function isRefundedPartially(ArrayCollection $paymentTransactions, Subtotal $total): bool
    {
        $refundTransactions = $this->getRefundedTransactions($paymentTransactions);
        return $this->isPartial($refundTransactions, $total);
    }

    protected function isPartial(ArrayCollection $paymentTransactions, Subtotal $total): bool
    {
        $transactionsAmount = $this->getTransactionAmounts($paymentTransactions);
        $totalAmount = BigDecimal::of($total->getAmount())->toScale(2, RoundingMode::HALF_UP);

        return $paymentTransactions->count()
            && BigDecimal::of($transactionsAmount)->isLessThan($totalAmount);
    }
}
