<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Brick\Math\BigDecimal;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SplitEntitiesProviderInterface;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\EventListener\DatagridLineItemsDataPricingListener;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItemInterface;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * Adds line items promotions data.
 */
class DatagridLineItemsDataPromotionsListener
{
    public const DISCOUNT_VALUE = 'discountValue';
    public const DISCOUNT = 'discount';
    public const INITIAL_SUBTOTAL = 'initialSubtotal';

    private PromotionExecutor $promotionExecutor;

    private NumberFormatter $numberFormatter;

    private SplitEntitiesProviderInterface $splitEntitiesProvider;

    private RoundingServiceInterface $roundingService;

    private array $cache = [];

    public function __construct(
        PromotionExecutor $promotionExecutor,
        NumberFormatter $numberFormatter,
        SplitEntitiesProviderInterface $splitEntitiesProvider,
        RoundingServiceInterface $roundingService
    ) {
        $this->promotionExecutor = $promotionExecutor;
        $this->numberFormatter = $numberFormatter;
        $this->splitEntitiesProvider = $splitEntitiesProvider;
        $this->roundingService = $roundingService;
    }

    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        $lineItems = $event->getLineItems();
        if (!$lineItems) {
            return;
        }

        $entity = $this->getMainEntity($lineItems);
        if (!$entity instanceof ProductLineItemsHolderInterface) {
            return;
        }

        $discountTotals = $this->getDiscountTotals($entity);
        if (!$discountTotals) {
            return;
        }

        foreach ($lineItems as $lineItem) {
            $lineItemId = $lineItem->getEntityIdentifier();
            $discountValue = $discountTotals[$lineItemId] ?? null;
            if (!$discountValue) {
                continue;
            }

            $lineItemData = $event->getDataForLineItem($lineItemId);
            if (
                empty($lineItemData[DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE])
                || empty($lineItemData[DatagridLineItemsDataPricingListener::CURRENCY])
            ) {
                $event->addDataForLineItem($lineItemId, [
                    self::DISCOUNT_VALUE => 0.0,
                    self::DISCOUNT => '',
                    self::INITIAL_SUBTOTAL => $lineItemData[DatagridLineItemsDataPricingListener::SUBTOTAL] ?? '',
                ]);
                continue;
            }

            $currency = $lineItemData[DatagridLineItemsDataPricingListener::CURRENCY];
            $subtotalValue = BigDecimal::of(($lineItemData[DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE] ?? 0))
                ->minus($discountValue)
                ->toFloat();
            $subtotalValue = $this->roundingService->round($subtotalValue);

            $event->addDataForLineItem($lineItemId, [
                self::DISCOUNT_VALUE => $discountValue,
                DatagridLineItemsDataPricingListener::SUBTOTAL_VALUE => $subtotalValue,
                self::DISCOUNT => $this->numberFormatter->formatCurrency($discountValue, $currency),
                self::INITIAL_SUBTOTAL => $lineItemData[DatagridLineItemsDataPricingListener::SUBTOTAL] ?? '',
                DatagridLineItemsDataPricingListener::SUBTOTAL => $this->numberFormatter
                    ->formatCurrency($subtotalValue, $currency),
            ]);
        }
    }

    private function getDiscountTotals(ProductLineItemsHolderInterface $lineItemsHolder): array
    {
        $id = $lineItemsHolder->getId() ?? spl_object_hash($lineItemsHolder);
        if (!isset($this->cache[$id]) && $this->promotionExecutor->supports($lineItemsHolder)) {
            // Check if create split order functionality enabled and apply discounts for each potential suborder
            // separately.
            $splitEntities = $this->splitEntitiesProvider->getSplitEntities($lineItemsHolder);

            if (!empty($splitEntities)) {
                $discounts = [];
                foreach ($splitEntities as $entity) {
                    $discounts[] = $this->getLineItemsDiscounts($entity);
                }

                // array_replace is used here just only to merge arrays with the integer keys. Its behaviour does
                // not impact the logic.
                $this->cache[$id] = array_replace([], ...$discounts);
            } else {
                $this->cache[$id] = $this->getLineItemsDiscounts($lineItemsHolder);
            }
        }

        return $this->cache[$id] ?? [];
    }

    public function getLineItemsDiscounts(ProductLineItemsHolderInterface $lineItemsHolder): array
    {
        $discountContext = $this->promotionExecutor->execute($lineItemsHolder);

        $discounts = [];
        $lineItemsDiscounts = [];

        /** @var DiscountLineItemInterface $item */
        foreach ($discountContext->getLineItems() as $item) {
            $identifier = $this->getDataKey($item->getSourceLineItem());

            $discounts[$identifier] = $item->getDiscountTotal();
        }

        foreach ($lineItemsHolder->getLineItems() as $item) {
            $identifier = $this->getDataKey($item);

            if (isset($discounts[$identifier])) {
                $lineItemsDiscounts[$item->getId()] = $discounts[$identifier];
            }
        }

        return $lineItemsDiscounts;
    }

    private function getMainEntity(array $lineItems): ?object
    {
        $entity = null;

        $lineItem = reset($lineItems);
        switch (true) {
            case $lineItem instanceof LineItem:
                $entity = $lineItem->getShoppingList();
                break;
            case $lineItem instanceof CheckoutLineItem:
                $entity = $lineItem->getCheckout();
                break;
            default:
                break;
        }

        return $entity;
    }

    private function getDataKey(ProductLineItemInterface $item): string
    {
        $dataKey = implode(':', [$item->getProductSku(), $item->getProductUnitCode(), $item->getQuantity()]);
        if ($item instanceof ProductKitItemLineItemsAwareInterface) {
            $dataKey .= ':' . $item->getChecksum();
        }

        return $dataKey;
    }
}
