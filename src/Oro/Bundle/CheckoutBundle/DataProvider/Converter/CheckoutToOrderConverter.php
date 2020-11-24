<?php

namespace Oro\Bundle\CheckoutBundle\DataProvider\Converter;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderAwareTrait;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\CheckoutBundle\Payment\Method\EntityPaymentMethodsProvider;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Returns order based on checkout
 */
class CheckoutToOrderConverter
{
    use MemoryCacheProviderAwareTrait;

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
     * @var EntityPaymentMethodsProvider
     */
    private $paymentMethodsProvider;

    /**
     * @param CheckoutLineItemsManager $checkoutLineItemsManager
     * @param MapperInterface $mapper
     * @param CacheProvider $orderCache
     * @param EntityPaymentMethodsProvider $paymentMethodsProvider
     */
    public function __construct(
        CheckoutLineItemsManager $checkoutLineItemsManager,
        MapperInterface $mapper,
        CacheProvider $orderCache,
        EntityPaymentMethodsProvider $paymentMethodsProvider
    ) {
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
        $this->mapper = $mapper;
        $this->orderCache = $orderCache;
        $this->paymentMethodsProvider = $paymentMethodsProvider;
    }

    /**
     * @param Checkout $checkout
     * @return Order
     */
    public function getOrder(Checkout $checkout)
    {
        $order = $this->getOrderUsingCache($checkout);

        $this->paymentMethodsProvider->storePaymentMethodsToEntity($order, [$checkout->getPaymentMethod()]);

        return $order;
    }

    /**
     * @param Checkout $checkout
     * @return Order
     */
    private function getOrderUsingCache(Checkout $checkout): Order
    {
        if ($this->memoryCacheProvider) {
            $order = $this->getMemoryCacheProvider()->get(
                ['checkout' => $checkout],
                function () use ($checkout) {
                    return $this->mapper->map(
                        $checkout,
                        [
                            'lineItems' => $this->checkoutLineItemsManager->getData($checkout),
                        ]
                    );
                }
            );
        } else {
            $hash = md5(serialize($checkout));
            $order = $this->orderCache->fetch($hash);
            if ($order === false) {
                $data = ['lineItems' => $this->checkoutLineItemsManager->getData($checkout)];
                $order = $this->mapper->map($checkout, $data);
                $this->orderCache->save($hash, $order);
            }
        }

        return $order;
    }
}
