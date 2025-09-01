<?php

namespace Oro\Bundle\PromotionBundle\Api\Processor;

use Brick\Math\BigDecimal;
use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItemInterface;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes values of "totalValue" and "discount" fields for ShoppingListLineItem entity.
 */
class ComputeShoppingListLineItemDiscount implements ProcessorInterface
{
    public function __construct(
        private readonly PromotionExecutor $promotionExecutor,
        private readonly DoctrineHelper $doctrineHelper,
        private readonly ValueTransformer $valueTransformer,
        private readonly ValueNormalizer $valueNormalizer,
        private readonly ProductLineItemPriceProviderInterface $productLineItemPriceProvider,
        private readonly ProductPriceScopeCriteriaFactoryInterface $productPriceScopeCriteriaFactory
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $config = $context->getConfig();
        $totalValueFieldName = $context->getResultFieldName('totalValue', $config);
        $discountFieldName = $context->getResultFieldName('discount', $config);
        $subtotalFieldName = $context->getResultFieldName('subTotal', $config);
        $isTotalValueFieldRequested = $context->isFieldRequested($totalValueFieldName);
        $isDiscountFieldRequested = $context->isFieldRequested($discountFieldName);
        $isSubtotalFieldRequested = $context->isFieldRequested($subtotalFieldName);
        if (!$isTotalValueFieldRequested && !$isDiscountFieldRequested) {
            return;
        }

        $data = $context->getData();
        $lineItemIdFieldName = $context->getResultFieldName('id', $config);
        $dataMap = $this->getDataMap($data, $lineItemIdFieldName);
        $mapOfLineItems = $this->getMapOfLineItems(array_keys($dataMap));
        $discounts = $this->getLineItemDiscounts($mapOfLineItems);
        $productPrices = $isTotalValueFieldRequested && !$isSubtotalFieldRequested
            ? $this->getProductPrices($mapOfLineItems)
            : [];
        $requestType = $context->getRequestType();
        $normalizationContext = $context->getNormalizationContext();
        foreach ($data as $key => $item) {
            $lineItemId = $item[$lineItemIdFieldName];
            $discountValue = $discounts[$lineItemId] ?? 0.0;
            if ($isTotalValueFieldRequested) {
                $subtotalValue = \array_key_exists($subtotalFieldName, $item)
                    ? $this->valueNormalizer->normalizeValue($item[$subtotalFieldName], DataType::MONEY, $requestType)
                    : ($productPrices[$lineItemId] ?? null)?->getSubtotal();
                $data[$key][$totalValueFieldName] = $this->valueTransformer->transformValue(
                    $this->getTotalValue($subtotalValue, $discountValue),
                    DataType::MONEY,
                    $normalizationContext
                );
            }
            if ($isDiscountFieldRequested) {
                $data[$key][$discountFieldName] = $this->valueTransformer->transformValue(
                    $this->getDiscountValue($discountValue),
                    DataType::MONEY,
                    $normalizationContext
                );
            }
        }
        $context->setData($data);
    }

    private function getDataMap(array $data, string $idFieldName): array
    {
        $dataMap = [];
        foreach ($data as $key => $item) {
            $dataMap[$item[$idFieldName]] = $key;
        }

        return $dataMap;
    }

    /**
     * @return array [line item id => discount, ...]
     */
    private function getLineItemDiscounts(array $mapOfLineItems): array
    {
        $discountValues = [];
        foreach ($mapOfLineItems as [$lineItems, $shoppingList]) {
            $discounts = [];
            $discountItems = $this->promotionExecutor->execute($shoppingList)->getLineItems();
            /** @var DiscountLineItemInterface $discountItem */
            foreach ($discountItems as $discountItem) {
                $discountItemKey = $this->getDiscountItemKey($discountItem->getSourceLineItem());
                $discounts[$discountItemKey] = $discountItem->getDiscountTotal();
            }
            /** @var LineItem $lineItem */
            foreach ($lineItems as $lineItem) {
                $discountItemKey = $this->getDiscountItemKey($lineItem);
                if (isset($discounts[$discountItemKey])) {
                    $discountValues[$lineItem->getId()] = $discounts[$discountItemKey];
                }
            }
        }

        return $discountValues;
    }

    /**
     * @return array<int, ?ProductLineItemPrice> [line item id => price, ...]
     */
    private function getProductPrices(array $mapOfLineItems): array
    {
        $result = [];
        foreach ($mapOfLineItems as [$lineItems, $shoppingList]) {
            $productLineItemPrices = $this->productLineItemPriceProvider->getProductLineItemsPrices(
                $lineItems,
                $this->productPriceScopeCriteriaFactory->createByContext($shoppingList),
                $shoppingList->getCurrency()
            );
            foreach ($productLineItemPrices as $productLineItemPrice) {
                $result[$productLineItemPrice->getLineItem()->getEntityIdentifier()] = $productLineItemPrice;
            }
        }

        return $result;
    }

    /**
     * @return array [shopping list id => [[line item, ...], shopping list], ...]
     */
    private function getMapOfLineItems(array $lineItemIds): array
    {
        $mapOfLineItems = [];
        $lineItems = $this->getLineItems($lineItemIds);
        foreach ($lineItems as $lineItem) {
            /** @var ShoppingList $shoppingList */
            $shoppingList = $lineItem->getShoppingList();
            if (!isset($mapOfLineItems[$shoppingList->getId()])) {
                $mapOfLineItems[$shoppingList->getId()] = [[], $shoppingList];
            }
            $mapOfLineItems[$shoppingList->getId()][0][] = $lineItem;
        }

        return $mapOfLineItems;
    }

    /**
     * @return LineItem[]
     */
    private function getLineItems(array $lineItemIds): array
    {
        return $this->doctrineHelper->createQueryBuilder(LineItem::class, 'li')
            ->select('li, sl, p, pu, pup, ki, kip, kipup, kii')
            ->leftJoin('li.shoppingList', 'sl')
            ->leftJoin('li.product', 'p')
            ->leftJoin('li.unit', 'pu')
            ->leftJoin('p.unitPrecisions', 'pup')
            ->leftJoin('li.kitItemLineItems', 'ki')
            ->leftJoin('ki.product', 'kip')
            ->leftJoin('kip.unitPrecisions', 'kipup')
            ->leftJoin('ki.kitItem', 'kii')
            ->where('li.id IN (:ids)')
            ->setParameter('ids', $lineItemIds)
            ->getQuery()
            ->setHint(Query::HINT_REFRESH, true)
            ->getResult();
    }

    private function getDiscountItemKey(ProductLineItemInterface $item): string
    {
        $dataKey = implode(':', [$item->getProductSku(), $item->getProductUnitCode(), $item->getQuantity()]);
        if ($item instanceof ProductKitItemLineItemsAwareInterface) {
            $dataKey .= ':' . $item->getChecksum();
        }

        return $dataKey;
    }

    private function getTotalValue(?float $subtotalValue, float $discountValue): ?float
    {
        return null !== $subtotalValue
            ? BigDecimal::of($subtotalValue)->minus($discountValue)->toFloat()
            : null;
    }

    private function getDiscountValue(float $discountValue): float
    {
        return BigDecimal::of(0)->minus($discountValue)->toFloat();
    }
}
