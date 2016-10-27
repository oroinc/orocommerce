<?php

namespace Oro\Bundle\CheckoutBundle\Action;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;

class DefaultShippingMethodSetter
{
    /**
     * @var ShippingContextProviderFactory
     */
    protected $contextProviderFactory;

    /**
     * @var ShippingPriceProvider
     */
    protected $priceProvider;

    /**
     * @param ShippingContextProviderFactory $contextProviderFactory
     * @param ShippingPriceProvider $priceProvider
     */
    public function __construct(
        ShippingContextProviderFactory $contextProviderFactory,
        ShippingPriceProvider $priceProvider
    ) {
        $this->contextProviderFactory = $contextProviderFactory;
        $this->priceProvider = $priceProvider;
    }

    /**
     * @param Checkout $checkout
     */
    public function setDefaultShippingMethod(Checkout $checkout)
    {
        if ($checkout->getShippingMethod()) {
            return;
        }
        $context = $this->contextProviderFactory->create($checkout);
        $methodsData = $this->priceProvider->getApplicableMethodsWithTypesData($context);
        if (count($methodsData) === 0) {
            return;
        }
        $methodData = reset($methodsData);
        $typeData = reset($methodData['types']);
        $checkout->setShippingMethod($methodData['identifier']);
        $checkout->setShippingMethodType($typeData['identifier']);
        $checkout->setShippingCost($typeData['price']);
    }
}
