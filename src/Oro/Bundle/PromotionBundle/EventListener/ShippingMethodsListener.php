<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\ShippingBundle\Event\ApplicableMethodsEvent;

/**
 * Listener for modification of shipping prices according promotions discounts
 */
class ShippingMethodsListener
{
    /**
     * @var PromotionExecutor
     */
    private $promotionExecutor;

    public function __construct(PromotionExecutor $promotionExecutor)
    {
        $this->promotionExecutor = $promotionExecutor;
    }

    public function modifyPrices(ApplicableMethodsEvent $event)
    {
        $methodCollection = $event->getMethodCollection();
        $sourceEntity = $event->getSourceEntity();

        if (!$sourceEntity instanceof Checkout || $sourceEntity->getSourceEntity() instanceof QuoteDemand) {
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
