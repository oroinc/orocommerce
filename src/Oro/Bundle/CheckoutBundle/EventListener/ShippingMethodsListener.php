<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutShippingContextFactory;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodsConfigsRulesProviderInterface;

class ShippingMethodsListener extends AbstractMethodsListener
{
    /**
     * @var ShippingMethodsConfigsRulesProviderInterface
     */
    private $shippingProvider;

    /**
     * @var CheckoutShippingContextFactory
     */
    private $contextFactory;

    /**
     * @param OrderAddressProvider $addressProvider
     * @param OrderAddressSecurityProvider $orderAddressSecurityProvider
     * @param OrderAddressManager $orderAddressManager
     * @param ShippingMethodsConfigsRulesProviderInterface $shippingProvider
     * @param CheckoutShippingContextFactory $contextFactory
     */
    public function __construct(
        OrderAddressProvider $addressProvider,
        OrderAddressSecurityProvider $orderAddressSecurityProvider,
        OrderAddressManager $orderAddressManager,
        ShippingMethodsConfigsRulesProviderInterface $shippingProvider,
        CheckoutShippingContextFactory $contextFactory
    ) {
        parent::__construct($addressProvider, $orderAddressSecurityProvider, $orderAddressManager);

        $this->shippingProvider = $shippingProvider;
        $this->contextFactory = $contextFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function hasMethodsConfigsForAddress(Checkout $checkout, OrderAddress $address = null)
    {
        $checkout->setShippingAddress($address);
        $shippingContext = $this->contextFactory->create($checkout);
        $shippingMethodsConfigs = $this->shippingProvider
            ->getFilteredShippingMethodsConfigsRegardlessDestination($shippingContext);
        return (bool) count($shippingMethodsConfigs);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAddressType()
    {
        return AddressType::TYPE_SHIPPING;
    }

    /**
     * {@inheritdoc}
     */
    protected function getError()
    {
        return 'oro.checkout.frontend.checkout.cannot_create_order_no_shipping_methods_available';
    }
}
