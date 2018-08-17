<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutShippingContextFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

class CheckoutShippingContextProvider
{
    /** @var array */
    private $shippingContextCache = [];

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
        $contextHash = md5(serialize($entity));
        if (isset($this->shippingContextCache[$contextHash])) {
            return $this->shippingContextCache[$contextHash];
        }

        $this->shippingContextCache[$contextHash] = $this->shippingContextFactory->create($entity);

        return $this->shippingContextCache[$contextHash];
    }
}
