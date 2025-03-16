<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes values for "currency" and "value" fields for a shopping list item.
 */
class ComputeShoppingListLineItemPrice implements ProcessorInterface
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

        $productPrice = $this->getProductPrice($data[$context->getResultFieldName('id')]);
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

    private function getProductPrice(int $lineItemId): ?Price
    {
        $lineItem = $this->getLineItem($lineItemId);
        if (null === $lineItem) {
            return null;
        }

        $shoppingList = $lineItem->getShoppingList();
        $productLineItemPrices = $this->productLineItemPriceProvider->getProductLineItemsPrices(
            [$lineItem],
            $this->productPriceScopeCriteriaFactory->createByContext($shoppingList),
            $shoppingList->getCurrency()
        );
        if (!isset($productLineItemPrices[0])) {
            return null;
        }

        return $productLineItemPrices[0]->getPrice();
    }

    private function getLineItem(int $lineItemId): ?LineItem
    {
        return $this->doctrineHelper->createQueryBuilder(LineItem::class, 'li')
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
