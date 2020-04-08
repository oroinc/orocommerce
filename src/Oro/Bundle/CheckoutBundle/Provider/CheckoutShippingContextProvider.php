<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderAwareTrait;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutShippingContextFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

/**
 * Provides shipping context for given checkout entity.
 */
class CheckoutShippingContextProvider
{
    use MemoryCacheProviderAwareTrait;

    /** @var CheckoutShippingContextFactory */
    private $checkoutShippingContextFactory;

    /**
     * @param CheckoutShippingContextFactory $checkoutShippingContextFactory
     */
    public function __construct(CheckoutShippingContextFactory $checkoutShippingContextFactory)
    {
        $this->checkoutShippingContextFactory = $checkoutShippingContextFactory;
    }

    /**
     * @param Checkout $entity
     *
     * @return ShippingContextInterface|null
     */
    public function getContext(Checkout $entity): ?ShippingContextInterface
    {
        return $this->getMemoryCacheProvider()->get(
            ['checkout' => $entity],
            function () use ($entity) {
                return $this->checkoutShippingContextFactory->create($entity);
            }
        );
    }
}
