<?php

namespace Oro\Bundle\CheckoutBundle\Action;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;

class DefaultShippingMethodSetter
{
    /**
     * @var CheckoutShippingMethodsProviderInterface
     */
    private $checkoutShippingMethodsProvider;

    public function __construct(CheckoutShippingMethodsProviderInterface $checkoutShippingMethodsProvider)
    {
        $this->checkoutShippingMethodsProvider = $checkoutShippingMethodsProvider;
    }

    public function setDefaultShippingMethod(Checkout $checkout)
    {
        if ($checkout->getShippingMethod()) {
            return;
        }

        $methodsDataCollection = $this->checkoutShippingMethodsProvider->getApplicableMethodsViews($checkout);

        if ($methodsDataCollection->isEmpty()) {
            return;
        }

        $methodsData = $methodsDataCollection->toArray();
        $methodData = reset($methodsData);
        $typeData = reset($methodData['types']);
        $checkout->setShippingMethod($methodData['identifier']);
        $checkout->setShippingMethodType($typeData['identifier']);
        $checkout->setShippingCost($typeData['price']);
    }
}
