<?php

namespace Oro\Bundle\PaymentTermBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;

class ResolvePaymentTermEvent extends Event
{
    const NAME = 'oro_payment_term.resolve.payment_term';

    /** @var PaymentTerm|null */
    protected $paymentTerm;

    /**
     * @param PaymentTerm|null $paymentTerm
     */
    public function __construct(PaymentTerm $paymentTerm = null)
    {
        $this->paymentTerm = $paymentTerm;
    }

    /**
     * @return PaymentTerm|null
     */
    public function getPaymentTerm()
    {
        return $this->paymentTerm;
    }

    /**
     * @param PaymentTerm|null $paymentTerm
     */
    public function setPaymentTerm(PaymentTerm $paymentTerm = null)
    {
        $this->paymentTerm = $paymentTerm;
    }
}
