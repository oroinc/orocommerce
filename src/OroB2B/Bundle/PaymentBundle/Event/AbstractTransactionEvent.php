<?php

namespace OroB2B\Bundle\PaymentBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class AbstractTransactionEvent extends Event
{
    const RETRIEVE = 'orob2b_payment.callback.retrieve';

    /** @var PaymentTransaction */
    protected $paymentTransaction;

    /** @var PaymentMethodInterface */
    protected $paymentMethod;

    /** @var array */
    protected $criteria = [];

    /**
     * @return PaymentTransaction
     */
    public function getPaymentTransaction()
    {
        return $this->paymentTransaction;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     */
    public function setPaymentTransaction(PaymentTransaction $paymentTransaction)
    {
        $this->paymentTransaction = $paymentTransaction;
    }

    /**
     * @return string
     */
    public function getRetrieveEventName()
    {
        if (!$this->paymentMethod) {
            return self::RETRIEVE;
        }

        return implode('.', [self::RETRIEVE, $this->paymentMethod->getType()]);
    }

    /**
     * @return PaymentMethodInterface
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * @param PaymentMethodInterface $paymentMethod
     */
    public function setPaymentMethod(PaymentMethodInterface $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * @return array
     */
    public function getCriteria()
    {
        if ($this->paymentMethod) {
            $this->criteria['paymentMethod'] = $this->paymentMethod->getType();
        }

        return $this->criteria;
    }

    /**
     * @param array $criteria
     */
    public function setCriteria(array $criteria)
    {
        $this->criteria = $criteria;
    }
}
