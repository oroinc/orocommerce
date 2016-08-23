<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Provider\CheckoutProvider;
use Oro\Bundle\PaymentBundle\Event\ResolvePaymentTermEvent;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;

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
