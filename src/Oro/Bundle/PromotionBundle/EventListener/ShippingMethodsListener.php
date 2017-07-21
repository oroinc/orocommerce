<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\ShippingBundle\Event\ApplicableMethodsEvent;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Listener for modification of shipping prices according promotions discounts
 */
class ShippingMethodsListener
{
    /**
     * @var PromotionExecutor
     */
    private $promotionExecutor;

    /**
     * @param PromotionExecutor $promotionExecutor
     */
    public function __construct(PromotionExecutor $promotionExecutor)
    {
        $this->promotionExecutor = $promotionExecutor;
    }

    /**
     * @param ApplicableMethodsEvent $event
     */
    public function modifyPrices(ApplicableMethodsEvent $event)
    {
        $methodCollection = $event->getMethodCollection();
        $sourceEntity = $event->getSourceEntity();

        if (!$sourceEntity instanceof Checkout || !$sourceEntity->getSourceEntity() instanceof ShoppingList) {
            return;
        }

        foreach ($methodCollection->getAllMethodsTypesViews() as $shippingMethodName => $methodTypes) {
            foreach ($methodTypes as $methodTypeId => $methodTypesView) {
                $methodTypesView['price'] = $this->calculateDiscountedPrice(
                    $sourceEntity,
                    $shippingMethodName,
                    $methodTypeId,
                    $methodTypesView['price']
                );

                $methodCollection->removeMethodTypeView($shippingMethodName, $methodTypeId);
                $methodCollection->addMethodTypeView($shippingMethodName, $methodTypeId, $methodTypesView);
            }
        }
    }

    /**
     * @param Checkout $checkoutSource
     * @param string $shippingMethod
     * @param string $methodTypeId
     * @param Price $shippingCost `
     * @return Price
     */
    private function calculateDiscountedPrice($checkoutSource, $shippingMethod, $methodTypeId, Price $shippingCost)
    {
        $checkout = clone $checkoutSource;

        $checkout->setShippingMethod($shippingMethod);
        $checkout->setShippingMethodType($methodTypeId);
        $checkout->setShippingCost($shippingCost);

        $discountContext = $this->promotionExecutor->execute($checkout);

        return Price::create(
            $shippingCost->getValue() - $discountContext->getShippingDiscountTotal(),
            $shippingCost->getCurrency()
        );
    }
}
