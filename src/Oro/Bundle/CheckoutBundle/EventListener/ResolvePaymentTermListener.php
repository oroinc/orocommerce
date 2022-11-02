<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\PaymentTermBundle\Event\ResolvePaymentTermEvent;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Change payment term in context with a checkout's source entity payment term value.
 */
class ResolvePaymentTermListener
{
    const CHECKOUT_ROUTE = 'oro_checkout_frontend_checkout';

    /** @var RequestStack */
    protected $requestStack;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var PaymentTermProviderInterface */
    private $paymentTermProvider;

    public function __construct(
        RequestStack $requestStack,
        ManagerRegistry $registry,
        PaymentTermProviderInterface $paymentTermProvider
    ) {
        $this->requestStack = $requestStack;
        $this->registry = $registry;
        $this->paymentTermProvider = $paymentTermProvider;
    }

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
