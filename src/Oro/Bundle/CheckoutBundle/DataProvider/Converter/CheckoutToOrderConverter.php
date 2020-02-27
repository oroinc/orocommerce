<?php

namespace Oro\Bundle\CheckoutBundle\DataProvider\Converter;

use Doctrine\Common\Cache\CacheProvider;
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
     * @param EntityPaymentMethodsProvider $paymentMethodsProvider
     */
    public function setPaymentMethodsProvider(EntityPaymentMethodsProvider $paymentMethodsProvider)
    {
        $this->paymentMethodsProvider = $paymentMethodsProvider;
    }

    /**
     * @param Checkout $checkout
     * @return Order
     */
    public function getOrder(Checkout $checkout)
    {
        $hash = md5(serialize($checkout));
        $order = $this->orderCache->fetch($hash);
        if ($order === false) {
            $data = ['lineItems' => $this->checkoutLineItemsManager->getData($checkout)];
            $order = $this->mapper->map($checkout, $data);
            $this->orderCache->save($hash, $order);
        }

        $this->paymentMethodsProvider->storePaymentMethodsToEntity($order, [$checkout->getPaymentMethod()]);

        return $order;
    }
}
