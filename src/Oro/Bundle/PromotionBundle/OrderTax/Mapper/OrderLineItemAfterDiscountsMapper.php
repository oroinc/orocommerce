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
    private TaxMapperInterface $innerMapper;
    private TaxationSettingsProvider $taxationSettingsProvider;
    private PromotionExecutor $promotionExecutor;
    private array $discountContexts = [];

    public function __construct(
        TaxMapperInterface $innerMapper,
        TaxationSettingsProvider $taxationSettingsProvider,
        PromotionExecutor $promotionExecutor
    ) {
        $this->innerMapper = $innerMapper;
        $this->taxationSettingsProvider = $taxationSettingsProvider;
        $this->promotionExecutor = $promotionExecutor;
    }

    /**
     * {@inheritdoc}
     * @param OrderLineItem $lineItem
     */
    public function map($lineItem)
    {
        $taxable = $this->innerMapper->map($lineItem);

        $order = $lineItem->getOrder();
        if ($order && !$order->getSubOrders()->isEmpty()) {
            $orders = $order->getSubOrders();
        } else {
            $orders = [$order];
        }

        foreach ($orders as $orderToProcess) {
            if ($lineItem->getPrice() &&
                $this->taxationSettingsProvider->isCalculateAfterPromotionsEnabled() &&
                $this->promotionExecutor->supports($orderToProcess)
            ) {
                $discountContext = $this->getDiscountContext($orderToProcess);
                $this->mapOrderLineItems($lineItem, $discountContext, $taxable);
            }
        }

        return $taxable;
    }

    private function mapOrderLineItems($lineItem, DiscountContextInterface $discountContext, Taxable $taxable): void
    {
        /** @var DiscountLineItemInterface $discountLineItem */
        foreach ($discountContext->getLineItems() as $discountLineItem) {
            if ($this->isLineItemTheSame($discountLineItem->getSourceLineItem(), $lineItem)) {
                $newPrice = BigDecimal::of($discountLineItem->getSubtotalAfterDiscounts())
                    ->dividedBy(
                        $taxable->getQuantity(),
                        TaxationSettingsProvider::CALCULATION_SCALE,
                        RoundingMode::HALF_UP
                    );

                $taxable->setPrice($newPrice);
                break;
            }
        }
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
}
