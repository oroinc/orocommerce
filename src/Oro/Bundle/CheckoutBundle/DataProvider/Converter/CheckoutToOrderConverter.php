<?php

namespace Oro\Bundle\CheckoutBundle\DataProvider\Converter;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Returns order based on checkout
 */
class CheckoutToOrderConverter
{
    /**
     * @var CacheProvider
     */
    private $orderCache;

    /**
     * @var CheckoutLineItemsManager
     */
    private $checkoutLineItemsManager;

    /**
     * @var MapperInterface
     */
    private $mapper;

    /**
     * @param CheckoutLineItemsManager $checkoutLineItemsManager
     * @param MapperInterface $mapper
     * @param CacheProvider $orderCache
     */
    public function __construct(
        CheckoutLineItemsManager $checkoutLineItemsManager,
        MapperInterface $mapper,
        CacheProvider $orderCache
    ) {
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
        $this->mapper = $mapper;
        $this->orderCache = $orderCache;
    }

    /**
     * @param Checkout $checkout
     * @return Order
     */
    public function getOrder(Checkout $checkout)
    {
        $hash = md5(serialize($checkout));
        $cachedResult = $this->orderCache->fetch($hash);
        if ($cachedResult !== false) {
            return $cachedResult;
        }

        $data = ['lineItems' => $this->checkoutLineItemsManager->getData($checkout)];

        $cachedResult = $this->mapper->map($checkout, $data);

        $this->orderCache->save($hash, $cachedResult);

        return $cachedResult;
    }
}
