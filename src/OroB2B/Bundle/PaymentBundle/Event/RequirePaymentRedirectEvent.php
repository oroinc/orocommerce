<?php

namespace OroB2B\Bundle\PaymentBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;

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
    private $redirect;

    /**
     * @param PaymentMethodInterface $paymentMethod
     */
    public function __construct(PaymentMethodInterface $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
        $this->redirect = false;
    }

    /**
     * @return bool
     */
    public function isRedirectNeeded()
    {
        return $this->redirect;
    }

    /**
     * @param bool $value
     */
    public function setRedirect($value)
    {
        $this->redirect = $value;
    }

    /**
     * @return PaymentMethodInterface
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }
}
