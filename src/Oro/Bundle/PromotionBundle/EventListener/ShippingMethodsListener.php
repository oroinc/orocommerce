<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\ShippingBundle\Event\ApplicableMethodsEvent;

/**
 * Modifies shipping prices according promotions discounts.
 */
class ShippingMethodsListener
{
    private PromotionExecutor $promotionExecutor;

    public function __construct(PromotionExecutor $promotionExecutor)
    {
        $this->promotionExecutor = $promotionExecutor;
    }

    public function modifyPrices(ApplicableMethodsEvent $event): void
    {
        $sourceEntity = $event->getSourceEntity();

        if (!$sourceEntity instanceof Checkout || $sourceEntity->getSourceEntity() instanceof QuoteDemand) {
            return;
        }

        $methodCollection = $event->getMethodCollection();
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

    private function calculateDiscountedPrice(
        Checkout $checkoutSource,
        string $shippingMethod,
        string $methodTypeId,
        Price $shippingCost
    ): Price {
        $checkout = clone $checkoutSource;

        $checkout->setShippingMethod($shippingMethod);
        $checkout->setShippingMethodType($methodTypeId);
        $checkout->setShippingCost($shippingCost);

        $discountContext = $this->promotionExecutor->execute($checkout);

        if ($discountContext->getShippingDiscountTotal() === 0.0) {
            return $shippingCost;
        }

        return Price::create(
            $shippingCost->getValue() - $discountContext->getShippingDiscountTotal(),
            $shippingCost->getCurrency()
        );
    }
}
