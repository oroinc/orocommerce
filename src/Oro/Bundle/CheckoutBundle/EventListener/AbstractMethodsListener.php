<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Oro\Component\Action\Event\ExtendableConditionEvent;

abstract class AbstractMethodsListener
{
    /**
     * @var OrderAddressProvider
     */
    private $addressProvider;

    /**
     * @var OrderAddressSecurityProvider
     */
    private $orderAddressSecurityProvider;

    /**
     * @var OrderAddressManager
     */
    private $orderAddressManager;

    /**
     * @param OrderAddressProvider $addressProvider
     * @param OrderAddressSecurityProvider $orderAddressSecurityProvider
     * @param OrderAddressManager $orderAddressManager
     */
    public function __construct(
        OrderAddressProvider $addressProvider,
        OrderAddressSecurityProvider $orderAddressSecurityProvider,
        OrderAddressManager $orderAddressManager
    ) {
        $this->addressProvider = $addressProvider;
        $this->orderAddressSecurityProvider = $orderAddressSecurityProvider;
        $this->orderAddressManager = $orderAddressManager;
    }

    /**
     * @return string
     */
    abstract protected function getAddressType();

    /**
     * @return string
     */
    abstract protected function getError();

    /**
     * @param Checkout $checkout
     * @param mixed|null $address
     * @return bool
     */
    abstract protected function hasMethodsConfigsForAddress(Checkout $checkout, OrderAddress $address = null);

    /**
     * @param ExtendableConditionEvent $event
     */
    public function onStartCheckout(ExtendableConditionEvent $event)
    {
        if (!$this->isApplicable($event)) {
            return;
        }

        $context = $event->getContext();
        /** @var Checkout $checkout */
        $checkout = clone $context['checkout'];

        $isManualEditGranted = $this->orderAddressSecurityProvider->isManualEditGranted($this->getAddressType());

        $hasMethodsConfigs = false;
        if ($isManualEditGranted) {
            $hasMethodsConfigs = $this->hasMethodsConfigsForAddress($checkout);
        } else {
            $availableAddresses = array_merge(
                $this->addressProvider->getCustomerAddresses($checkout->getCustomer(), $this->getAddressType()),
                $this->addressProvider->getCustomerUserAddresses(
                    $checkout->getCustomerUser(),
                    $this->getAddressType()
                )
            );

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

    /**
     * @param ExtendableConditionEvent $event
     * @return bool
     */
    private function isApplicable(ExtendableConditionEvent $event)
    {
        $context = $event->getContext();

        return $context instanceof ActionData && $context->get('checkout') instanceof Checkout
            && $context->get('validateOnStartCheckout');
    }
}
