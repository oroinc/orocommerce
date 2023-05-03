<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutShippingContextFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

/**
 * Provides shipping context for a specific checkout entity.
 */
class CheckoutShippingContextProvider
{
    private CheckoutShippingContextFactory $shippingContextFactory;
    private MemoryCacheProviderInterface $memoryCacheProvider;

    public function __construct(
        CheckoutShippingContextFactory $shippingContextFactory,
        MemoryCacheProviderInterface $memoryCacheProvider
    ) {
        $this->shippingContextFactory = $shippingContextFactory;
        $this->memoryCacheProvider = $memoryCacheProvider;
    }

    public function getContext(Checkout $entity): ShippingContextInterface
    {
        return $this->memoryCacheProvider->get(
            ['checkout' => $entity],
            function () use ($entity) {
                return $this->shippingContextFactory->create($entity);
            }
        );
    }
}
