<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutShippingContextFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

class CheckoutShippingContextProvider
{
    /** @var CheckoutShippingContextFactory */
    protected $shippingContextProviderFactory;

    /**
     * @param CheckoutShippingContextFactory $shippingContextProviderFactory
     */
    public function __construct(CheckoutShippingContextFactory $shippingContextProviderFactory)
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
