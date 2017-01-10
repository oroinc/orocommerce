<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutShippingContextFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

class CheckoutShippingContextProvider
{
    /** @var CheckoutShippingContextFactory */
    protected $shippingContextFactory;

    /**
     * @param CheckoutShippingContextFactory $shippingContextFactory
     */
    public function __construct(CheckoutShippingContextFactory $shippingContextFactory)
    {
        $this->shippingContextFactory = $shippingContextFactory;
    }

    /**
     * @param Checkout $entity
     * @return ShippingContextInterface
     */
    public function getContext(Checkout $entity)
    {
        return $this->shippingContextFactory->create($entity);
    }
}
