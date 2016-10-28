<?php

namespace Oro\Bundle\CheckoutBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class CheckoutValidateEvent extends Event
{
    const NAME = 'checkout.checkout_validate';

    /**
     * @var bool
     */
    protected $isCheckoutRestartRequired = false;

    /**
     * @var mixed
     */
    protected $context;

    /**
     * @param null|mixed $context
     */
    public function __construct($context = null)
    {
        $this->context = $context;
    }

    /**
     * @return boolean
     */
    public function isCheckoutRestartRequired(): bool
    {
        return $this->isCheckoutRestartRequired;
    }

    /**
     * @param boolean $isCheckoutRestartRequired
     *
     * @return $this
     */
    public function setIsCheckoutRestartRequired(bool $isCheckoutRestartRequired)
    {
        $this->isCheckoutRestartRequired = $isCheckoutRestartRequired;

        return $this;
    }

    /**
     * @return null|mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param mixed $context
     *
     * @return $this
     */
    public function setContext($context)
    {
        $this->context = $context;

        return $this;
    }
}
