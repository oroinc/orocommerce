<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Component\Action\Event\ExtendableConditionEvent;

abstract class AbstractMethodsListener
{
    /**
     * @var OrderAddressManager
     */
    private $orderAddressManager;

    public function __construct(OrderAddressManager $orderAddressManager)
    {
        $this->orderAddressManager = $orderAddressManager;
    }

    /**
     * @return string
     */
    abstract protected function getError();

    /**
     * @return bool
     */
    abstract protected function isManualEditGranted();

    /**
     * @param Checkout $checkout
     * @param OrderAddress|null $address
     * @return bool
     */
    abstract protected function hasMethodsConfigsForAddress(Checkout $checkout, OrderAddress $address = null);

    /**
     * @param Checkout $checkout
     * @return array
     */
    abstract protected function getApplicableAddresses(Checkout $checkout);

    final public function onStartCheckout(ExtendableConditionEvent $event)
    {
        if (!$this->isApplicable($event)) {
            return;
        }

        /** @var Checkout $checkout */
        $checkout = clone $event->getContext()?->offsetGet('checkout');

        $isManualEditGranted = $this->isManualEditGranted();

        $hasMethodsConfigs = false;
        if ($isManualEditGranted) {
            $hasMethodsConfigs = $this->hasMethodsConfigsForAddress($checkout);
        } else {
            $availableAddresses = $this->getApplicableAddresses($checkout);

            foreach ($availableAddresses as $address) {
                $orderAddress = $this->orderAddressManager->updateFromAbstract($address);
                $hasMethodsConfigs = $this->hasMethodsConfigsForAddress($checkout, $orderAddress);
                if ($hasMethodsConfigs) {
                    break;
                }
            }
        }

        if (!$hasMethodsConfigs) {
            $event->addError($this->getError());
        }
    }

    protected function isApplicable(ExtendableConditionEvent $event): bool
    {
        $context = $event->getContext();

        return $context
            && $context->offsetGet('checkout') instanceof Checkout
            && $context->offsetGet('validateOnStartCheckout');
    }
}
