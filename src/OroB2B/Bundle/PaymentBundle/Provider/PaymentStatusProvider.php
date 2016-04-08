<?php

namespace OroB2B\Bundle\PaymentBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class PaymentStatusProvider
{
    const FULL = 'full';
    const AUTHORIZED = 'authorized';
    const PENDING = 'pending';
    const DECLINED = 'declined';
    const PARTIALLY = 'partially';

    /** @var PaymentTransactionProvider */
    protected $paymentTransactionProvider;

    /**
     * @param PaymentTransactionProvider $paymentTransactionProvider
     */
    public function __construct(PaymentTransactionProvider $paymentTransactionProvider)
    {
        $this->paymentTransactionProvider = $paymentTransactionProvider;
    }

    /**
     * @param $object
     * @return string
     */
    public function getPaymentStatus($object)
    {
        $paymentTransactions = new ArrayCollection($this->paymentTransactionProvider->getPaymentTransactions($object));

        if ($this->hasSuccessfulTransactions($paymentTransactions)) {
            return self::FULL;
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
    protected function hasSuccessfulTransactions(ArrayCollection $paymentTransactions)
    {
        return false === $paymentTransactions
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
            )
            ->isEmpty();
    }

    /**
     * @param ArrayCollection $paymentTransactions
     * @return ArrayCollection
     */
    protected function hasAuthorizeTransactions(ArrayCollection $paymentTransactions)
    {
        return false === $paymentTransactions
            ->filter(
                function (PaymentTransaction $paymentTransaction) {
                    return $paymentTransaction->isActive()
                    && $paymentTransaction->isSuccessful()
                    && $paymentTransaction->getAction() === PaymentMethodInterface::AUTHORIZE;
                }
            )
            ->isEmpty();
    }

    /**
     * @param ArrayCollection $paymentTransactions
     * @return ArrayCollection
     */
    protected function hasDeclinedTransactions(ArrayCollection $paymentTransactions)
    {
        return false === $paymentTransactions
            ->filter(
                function (PaymentTransaction $paymentTransaction) {
                    return !$paymentTransaction->isSuccessful() && !$paymentTransaction->isActive();
                }
            )
            ->isEmpty();
    }
}
