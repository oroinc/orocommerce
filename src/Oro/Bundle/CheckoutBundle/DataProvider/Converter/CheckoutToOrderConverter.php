<?php

namespace Oro\Bundle\CheckoutBundle\DataProvider\Converter;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\CheckoutBundle\Payment\Method\EntityPaymentMethodsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SplitCheckoutProvider;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Returns an order created based on a specific checkout.
 */
class CheckoutToOrderConverter
{
    private CheckoutLineItemsManager $checkoutLineItemsManager;
    private MapperInterface $mapper;
    private EntityPaymentMethodsProvider $paymentMethodsProvider;
    private SplitCheckoutProvider $splitCheckoutProvider;
    private MemoryCacheProviderInterface $memoryCacheProvider;

    public function __construct(
        CheckoutLineItemsManager $checkoutLineItemsManager,
        MapperInterface $mapper,
        EntityPaymentMethodsProvider $paymentMethodsProvider,
        SplitCheckoutProvider $splitCheckoutProvider,
        MemoryCacheProviderInterface $memoryCacheProvider
    ) {
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
        $this->mapper = $mapper;
        $this->paymentMethodsProvider = $paymentMethodsProvider;
        $this->splitCheckoutProvider = $splitCheckoutProvider;
        $this->memoryCacheProvider = $memoryCacheProvider;
    }

    public function getOrder(Checkout $checkout): Order
    {
        $order = $this->memoryCacheProvider->get(
            ['checkout' => $checkout],
            fn () => $this->mapper->map($checkout, [
                'lineItems' => $this->checkoutLineItemsManager->getData($checkout)
            ])
        );

        $this->paymentMethodsProvider->storePaymentMethodsToEntity($order, [$checkout->getPaymentMethod()]);

        // Check if grouping line items is enabled and subOrders should be created.
        if ($checkout->getId()) {
            $splitCheckout = $this->splitCheckoutProvider->getSubCheckouts($checkout, false);
            if (!empty($splitCheckout)) {
                foreach ($splitCheckout as $subCheckout) {
                    $order->addSubOrder($this->getOrder($subCheckout));
                }
            }
        }

        return $order;
    }
}
