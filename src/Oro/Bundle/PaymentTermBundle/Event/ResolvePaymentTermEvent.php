<?php

namespace Oro\Bundle\PaymentTermBundle\Event;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched to resolve the payment term for an entity.
 *
 * This event allows listeners to determine or override the payment term that should be applied to a specific entity
 * (such as a customer or order). Listeners can set the payment term through the `setPaymentTerm()` method.
 */
class ResolvePaymentTermEvent extends Event
{
    public const NAME = 'oro_payment_term.resolve.payment_term';

    /** @var PaymentTerm|null */
    protected $paymentTerm;

    public function __construct(?PaymentTerm $paymentTerm = null)
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

    public function setPaymentTerm(?PaymentTerm $paymentTerm = null)
    {
        $this->paymentTerm = $paymentTerm;
    }
}
