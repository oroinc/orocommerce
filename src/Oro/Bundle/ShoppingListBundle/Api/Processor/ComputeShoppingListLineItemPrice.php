<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Computes values for "currency" and "value" fields for a shopping list item.
 */
class ComputeShoppingListLineItemPrice extends AbstractComputeLineItemPrice
{
    #[\Override]
    protected function getShoppingListLineItem(CustomizeLoadedDataContext $context): ?LineItem
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

        $productPrice = $this->getProductPrice($data[$context->getResultFieldName('id')]);
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

    private function getProductPrice(int $lineItemId): ?Price
    {
        $lineItem = $this->getLineItem($lineItemId);
        if (null === $lineItem) {
            return null;
        }

        $shoppingList = $lineItem->getShoppingList();
        $productLineItemPrices = $this->productLineItemPriceProvider->getProductLineItemsPrices(
            [$lineItem],
            $this->priceScopeCriteriaFactory->createByContext($shoppingList),
            $shoppingList->getCurrency()
        );
        if (!isset($productLineItemPrices[0])) {
            return null;
        }

        return $productLineItemPrices[0]->getPrice();
    }

    private function getLineItem(int $lineItemId): ?LineItem
    {
        return $this->managerRegistry->getRepository(LineItem::class)
            ->createQueryBuilder('li')
            ->select('li, sl, p, pu, ki, kii')
            ->leftJoin('li.shoppingList', 'sl')
            ->leftJoin('li.product', 'p')
            ->leftJoin('li.unit', 'pu')
            ->leftJoin('li.kitItemLineItems', 'ki')
            ->leftJoin('ki.kitItem', 'kii')
            ->where('li.id = :id')
            ->setParameter('id', $lineItemId)
            ->getQuery()
            ->setHint(Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }
}
