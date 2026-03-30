<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;

class OrderStub extends Order
{
    private ?PaymentTerm $paymentTerm;

    private ?PaymentTerm $customerPaymentTerm;

    private EnumOptionInterface|string|null $shippingStatus = null;

    public function getPaymentTerm(): ?PaymentTerm
    {
        return $this->paymentTerm;
    }

    public function setPaymentTerm(?PaymentTerm $paymentTerm): self
    {
        $this->paymentTerm = $paymentTerm;

        return $this;
    }

    public function getCustomerPaymentTerm(): ?PaymentTerm
    {
        return $this->customerPaymentTerm;
    }

    public function setCustomerPaymentTerm(?PaymentTerm $customerPaymentTerm): self
    {
        $this->customerPaymentTerm = $customerPaymentTerm;

        return $this;
    }

    public function getShippingStatus(): EnumOptionInterface|string|null
    {
        return $this->shippingStatus;
    }

    public function setShippingStatus(EnumOptionInterface|string|null $status): self
    {
        $this->shippingStatus = $status;

        return $this;
    }
}
