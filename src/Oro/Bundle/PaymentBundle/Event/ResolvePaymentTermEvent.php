<?php

namespace Oro\Bundle\PaymentBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;

class ResolvePaymentTermEvent extends Event
{
    const NAME = 'orob2b_payment.resolve.payment_term';

    /** @var PaymentTerm */
    protected $paymentTerm;

    /**
     * @return null|PaymentTerm
     */
    public function getPaymentTerm()
    {
        return $this->paymentTerm;
    }

    /**
     * @param PaymentTerm $paymentTerm
     */
    public function setPaymentTerm(PaymentTerm $paymentTerm)
    {
        $this->paymentTerm = $paymentTerm;
    }
}
