<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutShippingContextFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

/**
 * Provides checkout by shipping factory
 */
class CheckoutShippingContextProvider
{
    /** @var CacheProvider */
    private $cacheProvider;

    /** @var CheckoutShippingContextFactory */
    protected $shippingContextFactory;

    /**
     * @param CheckoutShippingContextFactory $shippingContextFactory
     * @param CacheProvider $cacheProvider
     */
    public function __construct(CheckoutShippingContextFactory $shippingContextFactory, CacheProvider $cacheProvider)
    {
        $this->shippingContextFactory = $shippingContextFactory;
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * @param Checkout $entity
     * @return ShippingContextInterface
     */
    public function getContext(Checkout $entity)
    {
        $contextHash = self::class . \md5(\serialize($entity));
        $cachedContext = $this->cacheProvider->fetch($contextHash);
        if (!$cachedContext) {
            $cachedContext = $this->shippingContextFactory->create($entity);
            $this->cacheProvider->save($contextHash, $cachedContext);
        }

        return $cachedContext;
    }
}
