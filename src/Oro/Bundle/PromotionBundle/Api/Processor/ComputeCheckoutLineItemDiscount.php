<?php

namespace Oro\Bundle\PromotionBundle\Api\Processor;

use Brick\Math\BigDecimal;
use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItemInterface;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes values for "totalValue" and "discount" fields for checkout line item entity.
 */
class ComputeCheckoutLineItemDiscount implements ProcessorInterface
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
        foreach ($mapOfLineItems as [$lineItems, $checkout]) {
            $discounts = [];
            $discountItems = $this->promotionExecutor->execute($checkout)->getLineItems();
            /** @var DiscountLineItemInterface $discountItem */
            foreach ($discountItems as $discountItem) {
                $discountItemKey = $this->getDiscountItemKey($discountItem->getSourceLineItem());
                $discounts[$discountItemKey] = $discountItem->getDiscountTotal();
            }
            /** @var CheckoutLineItem $lineItem */
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
        foreach ($mapOfLineItems as [$lineItems, $checkout]) {
            $productLineItemPrices = $this->productLineItemPriceProvider->getProductLineItemsPrices(
                $lineItems,
                $this->productPriceScopeCriteriaFactory->createByContext($checkout),
                $checkout->getCurrency()
            );
            foreach ($productLineItemPrices as $productLineItemPrice) {
                $result[$productLineItemPrice->getLineItem()->getEntityIdentifier()] = $productLineItemPrice;
            }
        }

        return $result;
    }

    /**
     * @return array [checkout id => [[line item, ...], checkout], ...]
     */
    private function getMapOfLineItems(array $lineItemIds): array
    {
        $mapOfLineItems = [];
        $lineItems = $this->getLineItems($lineItemIds);
        foreach ($lineItems as $lineItem) {
            /** @var Checkout $checkout */
            $checkout = $lineItem->getCheckout();
            if (!isset($mapOfLineItems[$checkout->getId()])) {
                $mapOfLineItems[$checkout->getId()] = [[], $checkout];
            }
            $mapOfLineItems[$checkout->getId()][0][] = $lineItem;
        }

        return $mapOfLineItems;
    }

    /**
     * @return CheckoutLineItem[]
     */
    private function getLineItems(array $lineItemIds): array
    {
        return $this->doctrineHelper->createQueryBuilder(CheckoutLineItem::class, 'li')
            ->select('li, c, p, pu, pup, ki, kip, kipup, kii')
            ->leftJoin('li.checkout', 'c')
            ->leftJoin('li.product', 'p')
            ->leftJoin('li.productUnit', 'pu')
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
        if (null === $subtotalValue) {
            return null;
        }

        return BigDecimal::of($subtotalValue)->minus($discountValue)->toFloat();
    }

    private function getDiscountValue(float $discountValue): float
    {
        return BigDecimal::of(0)->minus($discountValue)->toFloat();
    }
}
