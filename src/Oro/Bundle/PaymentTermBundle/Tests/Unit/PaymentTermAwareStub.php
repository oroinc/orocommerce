<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;

class PaymentTermAwareStub implements CustomerOwnerAwareInterface
{
    /** @var PaymentTerm */
    private $paymentTerm;

    /** @var Customer */
    private $customer;

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
    public function getCustomer()
    {
        return $this->customer;
    }

    /** {@inheritdoc} */
    public function getCustomerUser()
    {
    }

    /**
     * @param Customer|null $customer
     * @return PaymentTermAwareStub
     */
    public static function create(Customer $customer = null)
    {
        $self = new self();
        $self->customer = $customer;

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
