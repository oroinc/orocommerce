<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountOwnerAwareInterface;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;

class PaymentTermAwareStub implements AccountOwnerAwareInterface
{
    /** @var PaymentTerm */
    private $paymentTerm;

    /** @var Account */
    private $account;

    /**
     * @param PaymentTerm $paymentTerm
     */
    public function __construct(PaymentTerm $paymentTerm = null)
    {
        $this->paymentTerm = $paymentTerm;
    }

    /**
     * @return PaymentTerm
     */
    public function getPaymentTerm()
    {
        return $this->paymentTerm;
    }

    /** {@inheritdoc} */
    public function getAccount()
    {
        return $this->account;
    }

    /** {@inheritdoc} */
    public function getAccountUser()
    {
    }

    /**
     * @param Account|null $account
     * @return PaymentTermAwareStub
     */
    public static function create(Account $account = null)
    {
        $self = new self();
        $self->account = $account;

        return $self;
    }

    /**
     * @param PaymentTerm $paymentTerm
     * @return PaymentTermAwareStub
     */
    public function setPaymentTerm($paymentTerm)
    {
        $this->paymentTerm = $paymentTerm;

        return $this;
    }
}
