<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\PaymentTermBundle\Event\ResolvePaymentTermEvent;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;

use Symfony\Component\HttpFoundation\RequestStack;

class ResolvePaymentTermListener
{
    const CHECKOUT_ROUTE = 'oro_checkout_frontend_checkout';

    /** @var RequestStack */
    protected $requestStack;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var PaymentTermProvider */
    private $paymentTermProvider;

    /**
     * @param RequestStack $requestStack
     * @param ManagerRegistry $registry
     * @param PaymentTermProvider $paymentTermProvider
     */
    public function __construct(
        RequestStack $requestStack,
        ManagerRegistry $registry,
        PaymentTermProvider $paymentTermProvider
    ) {
        $this->requestStack = $requestStack;
        $this->registry = $registry;
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
     * @return null|Checkout
     */
    protected function getCurrentCheckout()
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request || $request->attributes->get('_route') != self::CHECKOUT_ROUTE) {
            return null;
        }

        return $this->getCheckoutEntity($request->attributes->get('id'));
    }

    /**
     * @param int $id
     * @return Checkout
     */
    private function getCheckoutEntity($id)
    {
        return $this->registry->getManagerForClass(Checkout::class)->find(Checkout::class, $id);
    }
}
