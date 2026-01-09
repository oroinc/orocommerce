<?php

namespace Oro\Bundle\PromotionBundle\OrderTax\Mapper;

use Brick\Math\BigDecimal;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItemInterface;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Component\Math\RoundingMode;

/**
 * Update Taxable price in case when option Calculate Taxes After Promotions is enabled
 */
class OrderLineItemAfterDiscountsMapper implements TaxMapperInterface
{
    private array $discountContexts = [];

    public function __construct(
        private TaxMapperInterface $innerMapper,
        private TaxationSettingsProvider $taxationSettingsProvider,
        private PromotionExecutor $promotionExecutor
    ) {
    }

    /**
     * @param object|OrderLineItem $lineItem
     */
    #[\Override]
    public function map(object $lineItem): Taxable
    {
        $taxable = $this->innerMapper->map($lineItem);

        $order = $lineItem->getOrder();
        if ($order && !$order->getSubOrders()->isEmpty()) {
            $orders = $order->getSubOrders();
        } else {
            $orders = [$order];
        }

        foreach ($orders as $order) {
            if (
                $lineItem->getPrice() &&
                $this->taxationSettingsProvider->isCalculateAfterPromotionsEnabled() &&
                $this->promotionExecutor->supports($order)
            ) {
                $discountContext = $this->getDiscountContext($order);

                /** @var DiscountLineItemInterface $discountLineItem */
                foreach ($discountContext->getLineItems() as $discountLineItem) {
                    if ($this->isLineItemTheSame($discountLineItem->getSourceLineItem(), $lineItem)) {
                        $this->adjustTaxable($taxable, $discountLineItem);
                        break;
                    }
                }
            }
        }

        return $taxable;
    }

    private function isLineItemTheSame(OrderLineItem $item1, OrderLineItem $item2): bool
    {
        return $item1->getProductSku() === $item2->getProductSku()
            && $item1->getQuantity() === $item2->getQuantity()
            && $item1->getProductUnitCode() === $item2->getProductUnitCode()
            && $item1->getValue() === $item2->getValue();
    }

    private function getDiscountContext(Order $order): DiscountContextInterface
    {
        $orderId = spl_object_id($order);
        if (!\array_key_exists($orderId, $this->discountContexts)) {
            $this->discountContexts[$orderId] = $this->promotionExecutor->execute($order);
        }

        return $this->discountContexts[$orderId];
    }

    private function adjustTaxable(Taxable $taxable, DiscountLineItemInterface $discountLineItem): void
    {
        $newPrice = BigDecimal::of($discountLineItem->getSubtotalAfterDiscounts())
            ->dividedBy(
                $taxable->getQuantity(),
                TaxationSettingsProvider::CALCULATION_SCALE,
                RoundingMode::HALF_UP
            );

        if (!$taxable->isKitTaxable()) {
            $taxable->setPrice($newPrice);
            return;
        }

        $delimiter = BigDecimal::of($discountLineItem->getPrice()->getValue());
        // Calculate general discount
        $discountPrice = $delimiter->minus($newPrice);

        // Calculate discount for kit taxable
        $kitDiscount = $taxable->getPrice()
            ->dividedBy(
                $delimiter,
                TaxationSettingsProvider::CALCULATION_SCALE,
                RoundingMode::HALF_UP
            )->multipliedBy($discountPrice);

        // Calculate new kit taxable price
        $newKitPrice = $taxable->getPrice()->minus($kitDiscount);
        $taxable->setPrice($newKitPrice);

        foreach ($taxable->getItems() as $item) {
            // Calculate discount for kit item taxable
            $kitItemDiscount = $item->getPrice()
                ->multipliedBy($item->getQuantity())
                ->dividedBy(
                    $delimiter,
                    TaxationSettingsProvider::CALCULATION_SCALE,
                    RoundingMode::HALF_UP
                )->multipliedBy($discountPrice);

            // Calculate new kit item taxable price
            $newKitItemPrice = $item->getPrice()
                ->minus($kitItemDiscount->dividedBy(
                    $item->getQuantity(),
                    TaxationSettingsProvider::CALCULATION_SCALE,
                    RoundingMode::HALF_UP
                ));
            $item->setPrice($newKitItemPrice);
        }
    }
}
