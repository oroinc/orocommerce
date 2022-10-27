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

    public function __construct(CheckoutShippingContextFactory $checkoutShippingContextFactory)
    {
        $this->checkoutShippingContextFactory = $checkoutShippingContextFactory;
    }

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
