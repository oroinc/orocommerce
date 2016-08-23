<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\CheckoutBundle\Event\CheckoutEvents;
use Oro\Bundle\CheckoutBundle\Event\CheckoutEntityEvent;

class CheckoutProvider
{
    const CHECKOUT_ROUTE = 'orob2b_checkout_frontend_checkout';

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
     * @return null|CheckoutInterface
     */
    public function getCurrent()
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request && $request->attributes->get('_route') == self::CHECKOUT_ROUTE) {
            $event = new CheckoutEntityEvent();
            $event->setCheckoutId($request->attributes->get('id'));
            $this->eventDispatcher->dispatch(CheckoutEvents::GET_CHECKOUT_ENTITY, $event);

            return $event->getCheckoutEntity();
        }

        return null;
    }
}
