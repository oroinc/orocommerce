<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\CheckoutBundle\Event\CheckoutEntityEvent;
use Oro\Bundle\CheckoutBundle\Event\CheckoutEvents;
use Oro\Bundle\PaymentBundle\Event\ResolvePaymentTermEvent;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;

class ResolvePaymentTermListener
{
    const CHECKOUT_ROUTE = 'oro_checkout_frontend_checkout';

    /** @var RequestStack */
    protected $requestStack;

    /** @var  EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param RequestStack $requestStack
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(RequestStack $requestStack, EventDispatcherInterface $eventDispatcher)
    {
        $this->requestStack = $requestStack;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param ResolvePaymentTermEvent $event
     */
    public function onResolvePaymentTerm(ResolvePaymentTermEvent $event)
    {
        $checkout = $this->getCurrentCheckout();
        if (!$checkout) {
            return;
        }

        $source = $checkout->getSourceEntity();
        if ($source && $source instanceof QuoteDemand && $source->getQuote()->getPaymentTerm()) {
            $event->setPaymentTerm($source->getQuote()->getPaymentTerm());
        }
    }

    /**
     * @return null|CheckoutInterface
     */
    protected function getCurrentCheckout()
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request || $request->attributes->get('_route') != self::CHECKOUT_ROUTE) {
            return null;
        }

        $event = new CheckoutEntityEvent();
        $event->setCheckoutId($request->attributes->get('id'));
        $this->eventDispatcher->dispatch(CheckoutEvents::GET_CHECKOUT_ENTITY, $event);

        return $event->getCheckoutEntity();
    }
}
