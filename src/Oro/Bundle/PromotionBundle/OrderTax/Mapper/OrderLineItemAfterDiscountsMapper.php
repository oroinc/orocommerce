<?php

namespace Oro\Bundle\PromotionBundle\OrderTax\Mapper;

use Brick\Math\BigDecimal;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItemInterface;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\TaxBundle\Mapper\TaxMapperInterface;
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
    private ?DiscountContextInterface $discountContext = null;

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

        if ($lineItem->getPrice() &&
            $this->taxationSettingsProvider->isCalculateAfterPromotionsEnabled() &&
            $this->promotionExecutor->supports($order)
        ) {
            $discountContext = $this->getDiscountContext($order);

            /** @var DiscountLineItemInterface $discountLineItem */
            foreach ($discountContext->getLineItems() as $discountLineItem) {
                if ($discountLineItem->getSourceLineItem() === $lineItem) {
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

        return $taxable;
    }

    private function getDiscountContext(Order $order): DiscountContextInterface
    {
        if (null === $this->discountContext) {
            $this->discountContext = $this->promotionExecutor->execute($order);
        }

        return $this->discountContext;
    }
}
