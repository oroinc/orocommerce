<?php

namespace Oro\Bundle\PaymentBundle\Event;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Symfony\Contracts\EventDispatcher\Event;

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
