<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes values for "currency", "value" and "subTotal" fields for a shopping list items.
 */
class ComputeShoppingListLineItemPrice implements ProcessorInterface, FeatureCheckerAwareInterface
{
    use FeatureCheckerHolderTrait;

    public function __construct(
        private readonly ProductLineItemPriceProviderInterface $productLineItemPriceProvider,
        private readonly ProductPriceScopeCriteriaFactoryInterface $productPriceScopeCriteriaFactory,
        private readonly ValueTransformer $valueTransformer,
        private readonly DoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $config = $context->getConfig();
        $valueFieldName = $context->getResultFieldName('value', $config);
        $currencyFieldName = $context->getResultFieldName('currency', $config);
        $subtotalFieldName = $context->getResultFieldName('subTotal', $config);
        $isValueFieldRequested = $context->isFieldRequested($valueFieldName);
        $isCurrencyFieldRequested = $context->isFieldRequested($currencyFieldName);
        $isSubtotalFieldRequested = $context->isFieldRequested($subtotalFieldName);
        if (!$isValueFieldRequested && !$isCurrencyFieldRequested && !$isSubtotalFieldRequested) {
            return;
        }

        $data = $context->getData();
        $dataMap = $this->getDataMap($data, $context->getResultFieldName('id', $config));
        $productPrices = $this->getProductPrices(array_keys($dataMap));
        $normalizationContext = $context->getNormalizationContext();
        foreach ($dataMap as $id => $key) {
            $productPrice = $productPrices[$id] ?? null;
            if ($isValueFieldRequested) {
                $data[$key][$valueFieldName] = $this->normalizeValue(
                    $productPrice?->getPrice()?->getValue(),
                    $config->getField($valueFieldName),
                    $normalizationContext
                );
            }
            if ($isCurrencyFieldRequested) {
                $data[$key][$currencyFieldName] = $productPrice?->getPrice()?->getCurrency();
            }
            if ($isSubtotalFieldRequested) {
                $data[$key][$subtotalFieldName] = $this->normalizeValue(
                    $productPrice?->getSubtotal(),
                    $config->getField($subtotalFieldName),
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
     * @return array<int, ?ProductLineItemPrice> [line item id => price, ...]
     */
    private function getProductPrices(array $lineItemIds): array
    {
        $result = [];
        $mapOfLineItems = $this->getMapOfLineItems($lineItemIds);
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
            $shoppingList = $lineItem->getAssociatedList();
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
        $qb = $this->doctrineHelper->createQueryBuilder(LineItem::class, 'li')
            ->select('li, sl, p, pu, ki, kii')
            ->leftJoin('li.product', 'p')
            ->leftJoin('li.unit', 'pu')
            ->leftJoin('li.kitItemLineItems', 'ki')
            ->leftJoin('ki.kitItem', 'kii')
            ->where('li.id IN (:ids)')
            ->setParameter('ids', $lineItemIds);

        if ($this->isFeaturesEnabled()) {
            $qb
                ->addSelect(['sfl'])
                ->leftJoin('li.savedForLaterList', 'sfl')
                ->leftJoin('li.shoppingList', 'sl');
        } else {
            $qb
                ->innerJoin('li.shoppingList', 'sl');
        }

        return $qb
            ->getQuery()
            ->setHint(Query::HINT_REFRESH, true)
            ->getResult();
    }

    private function normalizeValue(mixed $value, EntityDefinitionFieldConfig $config, array $context): mixed
    {
        return $this->valueTransformer->transformFieldValue($value, $config->toArray(true), $context);
    }
}
