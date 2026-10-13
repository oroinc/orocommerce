<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Mapper\MultiShipping;

use Brick\Math\BigDecimal;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

/**
 * Adjusts the shipping taxable amount for multi-shipping orders
 * when taxes are calculated after promotions.
 */
class OrderAfterDiscountsMapperDecorator implements TaxMapperInterface
{
    public function __construct(
        private readonly TaxMapperInterface $innerMapper,
        private readonly TaxationSettingsProvider $taxationSettingsProvider,
        private readonly PromotionExecutor $promotionExecutor
    ) {
    }

    #[\Override]
    public function map(object $order): Taxable
    {
        $taxable = $this->innerMapper->map($order);

        if (
            !$order instanceof Order
            || $order->getSubOrders()->isEmpty()
            || !$this->taxationSettingsProvider->isCalculateAfterPromotionsEnabled()
            || null === $taxable->getShippingCost()
        ) {
            return $taxable;
        }

        $shippingCost = $taxable->getShippingCost();
        foreach ($order->getSubOrders() as $subOrder) {
            if (!$this->promotionExecutor->supports($subOrder)) {
                continue;
            }
            $discountContext = $this->promotionExecutor->execute($subOrder);
            $shippingCost = $shippingCost->minus(
                $discountContext->getShippingDiscountTotal()
            );
        }
        $taxable->setShippingCost(
            BigDecimal::max(BigDecimal::zero(), $shippingCost)
        );

        return $taxable;
    }
}
