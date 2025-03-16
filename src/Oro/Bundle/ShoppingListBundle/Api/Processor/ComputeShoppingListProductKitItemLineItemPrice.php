<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitLineItemPrice;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Computes values for "currency" and "value" fields for a shopping list kit item.
 */
class ComputeShoppingListProductKitItemLineItemPrice extends AbstractComputeLineItemPrice
{
    #[\Override]
    protected function getShoppingListLineItem(CustomizeLoadedDataContext $context): ?LineItem
    {
        return null;
    }

    #[\Override]
    protected function getProductLineItemPrice(CustomizeLoadedDataContext $context): ?ProductLineItemPrice
    {
        return null;
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
            $priceValue = $productPrice?->getValue();
            if (null !== $priceValue) {
                $priceValue = (string)$priceValue;
            }
            $data[$valueFieldName] = $priceValue;
        }
        if ($isCurrencyFieldRequested) {
            $data[$currencyFieldName] = $productPrice?->getCurrency();
        }
        $context->setData($data);
    }

    private function getProductPrice(int $kitItemLineItemId, int $kitItemId): ?Price
    {
        $productLineItemPrice = $this->getProductLineItemPriceNew($kitItemLineItemId);
        if (!$productLineItemPrice instanceof ProductKitLineItemPrice) {
            return null;
        }

        $kitItemLineItemPrices = $productLineItemPrice->getKitItemLineItemPrices();
        if (!isset($kitItemLineItemPrices[$kitItemId])) {
            return null;
        }

        return $kitItemLineItemPrices[$kitItemId]->getPrice();
    }

    private function getProductLineItemPriceNew(int $kitItemLineItemId): ?ProductLineItemPrice
    {
        $lineItem = $this->getLineItem($kitItemLineItemId);
        if (null === $lineItem) {
            return null;
        }

        $shoppingList = $lineItem->getShoppingList();
        $productLineItemPrices = $this->productLineItemPriceProvider->getProductLineItemsPrices(
            [$lineItem],
            $this->priceScopeCriteriaFactory->createByContext($shoppingList),
            $shoppingList->getCurrency()
        );

        return $productLineItemPrices[0] ?? null;
    }

    private function getLineItem(int $kitItemLineItemId): ?LineItem
    {
        /** @var ProductKitItemLineItem|null $kitItemLineItem */
        $kitItemLineItem = $this->managerRegistry->getRepository(ProductKitItemLineItem::class)
            ->createQueryBuilder('kli')
            ->select('kli, li, sl, p, pu, ki, kii')
            ->leftJoin('kli.lineItem', 'li')
            ->leftJoin('li.shoppingList', 'sl')
            ->leftJoin('li.product', 'p')
            ->leftJoin('li.unit', 'pu')
            ->leftJoin('li.kitItemLineItems', 'ki')
            ->leftJoin('ki.kitItem', 'kii')
            ->where('kli.id = :id')
            ->setParameter('id', $kitItemLineItemId)
            ->getQuery()
            ->setHint(Query::HINT_REFRESH, true)
            ->getOneOrNullResult();

        return $kitItemLineItem?->getLineItem();
    }

    private function getKitItemId(array $data, CustomizeLoadedDataContext $context): int
    {
        $kitItemFieldName = $context->getResultFieldName('kitItem');

        return $data[$kitItemFieldName]['id'];
    }
}
