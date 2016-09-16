<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

class CheckoutShippingContextProvider
{
    /** @var ShippingContextProviderFactory */
    protected $shippingContextProviderFactory;

    /**
     * @param ShippingContextProviderFactory $shippingContextProviderFactory
     */
    public function __construct(ShippingContextProviderFactory $shippingContextProviderFactory)
    {
        $this->shippingContextProviderFactory = $shippingContextProviderFactory;
    }

    /**
     * @param Checkout $entity
     * @return ShippingContextInterface
     */
    public function getContext(Checkout $entity)
    {
        return $this->shippingContextProviderFactory->create($entity);
    }
}
