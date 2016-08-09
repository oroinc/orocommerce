<?php

namespace OroB2B\Bundle\CheckoutBundle\EventListener;

use OroB2B\Bundle\CheckoutBundle\Provider\CheckoutProvider;
use OroB2B\Bundle\PaymentBundle\Event\ResolvePaymentTermEvent;
use OroB2B\Bundle\SaleBundle\Entity\QuoteDemand;

class ResolvePaymentTermListener
{
    /** @var CheckoutProvider  */
    protected $checkoutProvider;

    /**
     * @param CheckoutProvider $checkoutProvider
     */
    public function __construct(CheckoutProvider $checkoutProvider)
    {
        $this->checkoutProvider = $checkoutProvider;
    }

    /**
     * @param ResolvePaymentTermEvent $event
     */
    public function onResolvePaymentTerm(ResolvePaymentTermEvent $event)
    {
        $checkout = $this->checkoutProvider->getCurrent();
        if (!$checkout) {
            return;
        }

        $source = $checkout->getSourceEntity();
        if ($source && $source instanceof QuoteDemand && $source->getQuote()->getPaymentTerm()) {
            $event->setPaymentTerm($source->getQuote()->getPaymentTerm());
        }
    }
}
