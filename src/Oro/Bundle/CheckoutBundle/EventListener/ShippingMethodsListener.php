<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Oro\Bundle\ShippingBundle\Provider\MethodsConfigsRule\Context\MethodsConfigsRulesByContextProviderInterface;

/**
 * Checks if there available shipping methods when checkout starts.
 */
class ShippingMethodsListener extends AbstractMethodsListener
{
    /**
     * @var MethodsConfigsRulesByContextProviderInterface
     */
    private $shippingProvider;

    /**
     * @var CheckoutShippingContextProvider
     */
    private $checkoutShippingContextProvider;

    /**
     * @var OrderAddressProvider
     */
    private $addressProvider;

    /**
     * @var OrderAddressSecurityProvider
     */
    private $orderAddressSecurityProvider;

    public function __construct(
        OrderAddressProvider $addressProvider,
        OrderAddressSecurityProvider $orderAddressSecurityProvider,
        OrderAddressManager $orderAddressManager,
        MethodsConfigsRulesByContextProviderInterface $shippingProvider,
        CheckoutShippingContextProvider $checkoutShippingContextProvider
    ) {
        parent::__construct($orderAddressManager);

        $this->addressProvider = $addressProvider;
        $this->orderAddressSecurityProvider = $orderAddressSecurityProvider;
        $this->shippingProvider = $shippingProvider;
        $this->checkoutShippingContextProvider = $checkoutShippingContextProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function hasMethodsConfigsForAddress(Checkout $checkout, OrderAddress $address = null)
    {
        $checkout->setShippingAddress($address);
        $shippingContext = $this->checkoutShippingContextProvider->getContext($checkout);
        $shippingMethodsConfigs = $this->shippingProvider->getShippingMethodsConfigsRules($shippingContext);

        return count($shippingMethodsConfigs) > 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function getError()
    {
        return 'oro.shipping.methods.no_method';
    }

    /**
     * {@inheritdoc}
     */
    protected function isManualEditGranted()
    {
        // User can ship to billing address so we have to count manual edit on billing address too.
        return $this->orderAddressSecurityProvider->isManualEditGranted(AddressType::TYPE_SHIPPING)
            || $this->orderAddressSecurityProvider->isManualEditGranted(AddressType::TYPE_BILLING);
    }

    /**
     * {@inheritdoc}
     */
    protected function getApplicableAddresses(Checkout $checkout)
    {
        // User can ship to billing address so we have to count billing addresses too.
        return array_merge(
            $this->addressProvider->getCustomerAddresses($checkout->getCustomer(), AddressType::TYPE_SHIPPING),
            $this->addressProvider->getCustomerUserAddresses($checkout->getCustomerUser(), AddressType::TYPE_SHIPPING),
            $this->addressProvider->getCustomerAddresses($checkout->getCustomer(), AddressType::TYPE_BILLING),
            $this->addressProvider->getCustomerUserAddresses($checkout->getCustomerUser(), AddressType::TYPE_BILLING)
        );
    }
}
