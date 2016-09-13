<?php

namespace Oro\Bundle\PaymentBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class RequirePaymentRedirectEvent extends Event
{
    const EVENT_NAME = 'oro_payment.require_payment_redirect';

    /**
     * @var PaymentMethodInterface
     */
    private $paymentMethod;

    /**
     * @var bool
     */
    private $redirectRequired;

    /**
     * @param PaymentMethodInterface $paymentMethod
     */
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
