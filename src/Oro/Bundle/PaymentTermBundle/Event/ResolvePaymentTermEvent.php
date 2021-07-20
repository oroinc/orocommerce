<?php

namespace Oro\Bundle\PaymentTermBundle\Event;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Symfony\Contracts\EventDispatcher\Event;

class ResolvePaymentTermEvent extends Event
{
    const NAME = 'oro_payment_term.resolve.payment_term';

    /** @var PaymentTerm|null */
    protected $paymentTerm;

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

    public function setPaymentTerm(PaymentTerm $paymentTerm = null)
    {
        $this->paymentTerm = $paymentTerm;
    }
}
