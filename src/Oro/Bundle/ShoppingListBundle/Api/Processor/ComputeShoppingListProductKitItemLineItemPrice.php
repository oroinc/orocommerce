<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitLineItemPrice;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes values for "currency" and "value" fields for a shopping list kit item.
 */
class ComputeShoppingListProductKitItemLineItemPrice implements ProcessorInterface
{
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

        $data = $context->getData();
        $valueFieldName = $context->getResultFieldName('value');
        $currencyFieldName = $context->getResultFieldName('currency');
        $isValueFieldRequested = $context->isFieldRequested($valueFieldName, $data);
        $isCurrencyFieldRequested = $context->isFieldRequested($currencyFieldName, $data);
        if (!$isValueFieldRequested && !$isCurrencyFieldRequested) {
            return;
        }

        $productPrice = $this->getProductPrice(
            $data[$context->getResultFieldName('id')],
            $this->getKitItemId($data, $context)
        );
        if ($isValueFieldRequested) {
            $data[$valueFieldName] = $this->valueTransformer->transformFieldValue(
                $productPrice?->getValue(),
                $context->getConfig()->getField($valueFieldName)->toArray(true),
                $context->getNormalizationContext()
            );
        }
        if ($isCurrencyFieldRequested) {
            $data[$currencyFieldName] = $productPrice?->getCurrency();
        }
        $context->setData($data);
    }

    private function getProductPrice(int $kitItemLineItemId, int $kitItemId): ?Price
    {
        $productLineItemPrice = $this->getProductLineItemPrice($kitItemLineItemId);
        if (!$productLineItemPrice instanceof ProductKitLineItemPrice) {
            return null;
        }

        $kitItemLineItemPrices = $productLineItemPrice->getKitItemLineItemPrices();
        if (!isset($kitItemLineItemPrices[$kitItemId])) {
            return null;
        }

        return $kitItemLineItemPrices[$kitItemId]->getPrice();
    }

    private function getProductLineItemPrice(int $kitItemLineItemId): ?ProductLineItemPrice
    {
        $lineItem = $this->getLineItem($kitItemLineItemId);
        if (null === $lineItem) {
            return null;
        }

        $shoppingList = $lineItem->getShoppingList();
        $productLineItemPrices = $this->productLineItemPriceProvider->getProductLineItemsPrices(
            [$lineItem],
            $this->productPriceScopeCriteriaFactory->createByContext($shoppingList),
            $shoppingList->getCurrency()
        );

        return $productLineItemPrices[0] ?? null;
    }

    private function getLineItem(int $kitItemLineItemId): ?LineItem
    {
        $em = $this->doctrineHelper->getEntityManagerForClass(ProductKitItemLineItem::class);
        /** @var ProductKitItemLineItem|null $kitItemLineItem */
        $kitItemLineItem = $em->getReference(ProductKitItemLineItem::class, $kitItemLineItemId);
        if (null !== $kitItemLineItem) {
            $em->refresh($kitItemLineItem);
        }

        return $kitItemLineItem?->getLineItem();
    }

    private function getKitItemId(array $data, CustomizeLoadedDataContext $context): int
    {
        $kitItemFieldName = $context->getResultFieldName('kitItem');
        $kitItemIdFieldName = $context->getResultFieldName(
            'id',
            $context->getConfig()->getField($kitItemFieldName)->getTargetEntity()
        );

        return $data[$kitItemFieldName][$kitItemIdFieldName];
    }
}
