<?php

namespace Oro\Bundle\PaymentBundle\Event;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched to determine if a payment method requires redirecting the user to an external payment gateway.
 *
 * This event allows listeners to indicate whether a specific payment method requires
 * user redirection to complete the payment process, such as redirecting to a third-party
 * payment processor.
 */
class RequirePaymentRedirectEvent extends Event
{
    public const EVENT_NAME = 'oro_payment.require_payment_redirect';

    /**
     * @var PaymentMethodInterface
     */
    private $paymentMethod;

    /**
     * @var bool
     */
    private $redirectRequired;

    public function __construct(PaymentMethodInterface $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
        $this->redirectRequired = false;
    }

    /**
     * @return bool
     */
    public function isRedirectRequired()
    {
        return $this->redirectRequired;
    }

    /**
     * @param bool $value
     */
    public function setRedirectRequired($value)
    {
        $this->redirectRequired = (bool)$value;
    }

    /**
     * @return PaymentMethodInterface
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }
}
