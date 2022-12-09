<?php

namespace Oro\Bundle\CheckoutBundle\DataProvider\Converter;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderAwareTrait;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\CheckoutBundle\Payment\Method\EntityPaymentMethodsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SplitCheckoutProvider;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Returns order based on checkout
 */
class CheckoutToOrderConverter
{
    use MemoryCacheProviderAwareTrait;

    private CheckoutLineItemsManager $checkoutLineItemsManager;
    private MapperInterface $mapper;
    private EntityPaymentMethodsProvider $paymentMethodsProvider;
    private ?SplitCheckoutProvider $splitCheckoutProvider = null;

    public function __construct(
        CheckoutLineItemsManager $checkoutLineItemsManager,
        MapperInterface $mapper,
        EntityPaymentMethodsProvider $paymentMethodsProvider
    ) {
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
        $this->mapper = $mapper;
        $this->paymentMethodsProvider = $paymentMethodsProvider;
    }

    public function setSplitCheckoutProvider(SplitCheckoutProvider $provider)
    {
        $this->splitCheckoutProvider = $provider;
    }

    public function getOrder(Checkout $checkout): Order
    {
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

        $this->paymentMethodsProvider->storePaymentMethodsToEntity($order, [$checkout->getPaymentMethod()]);

        // Check if grouping line items is enabled and subOrders should be created.
        if ($checkout->getId() && $this->splitCheckoutProvider) {
            $splitCheckout = $this->splitCheckoutProvider->getSubCheckouts($checkout, false);

            if (!empty($splitCheckout)) {
                foreach ($splitCheckout as $subCheckout) {
                    $subOrder = $this->getOrder($subCheckout);
                    $order->addSubOrder($subOrder);
                }
            }
        }

        return $order;
    }
}
