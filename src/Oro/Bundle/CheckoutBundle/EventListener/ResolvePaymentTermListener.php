<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\CheckoutBundle\Event\CheckoutEntityEvent;
use Oro\Bundle\CheckoutBundle\Event\CheckoutEvents;
use Oro\Bundle\PaymentTermBundle\Event\ResolvePaymentTermEvent;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ResolvePaymentTermListener
{
    const CHECKOUT_ROUTE = 'oro_checkout_frontend_checkout';

    /** @var RequestStack */
    protected $requestStack;

    /** @var  EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var PaymentTermProvider */
    private $paymentTermProvider;

    /**
     * @param RequestStack $requestStack
     * @param EventDispatcherInterface $eventDispatcher
     * @param PaymentTermProvider $paymentTermProvider
     */
    public function __construct(
        RequestStack $requestStack,
        EventDispatcherInterface $eventDispatcher,
        PaymentTermProvider $paymentTermProvider
    ) {
        $this->requestStack = $requestStack;
        $this->eventDispatcher = $eventDispatcher;
        $this->paymentTermProvider = $paymentTermProvider;
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
        if (!$source) {
            return;
        }

        $paymentTerm = $this->paymentTermProvider->getObjectPaymentTerm($source);
        if (!$paymentTerm) {
            return;
        }

        $event->setPaymentTerm($paymentTerm);
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
