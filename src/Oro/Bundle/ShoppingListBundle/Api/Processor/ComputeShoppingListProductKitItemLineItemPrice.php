<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitLineItemPrice;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;

/**
 * Computes values for "currency" and "value" fields for a shopping list kit item.
 */
class ComputeShoppingListProductKitItemLineItemPrice extends AbstractComputeLineItemPrice
{
    #[\Override]
    protected function getShoppingListLineItem(CustomizeLoadedDataContext $context): ?LineItem
    {
        $config = $context->getConfig();
        $idFieldName = $config->findFieldNameByPropertyPath('id');

        $entityManager = $this->managerRegistry->getManagerForClass(ProductKitItemLineItem::class);
        /** @var ProductKitItemLineItem|null $kitItemLineItem */
        $kitItemLineItem = $entityManager
            ->getReference(ProductKitItemLineItem::class, $context->getData()[$idFieldName]);
        if ($kitItemLineItem === null) {
            return null;
        }

        $entityManager->refresh($kitItemLineItem);

        return $kitItemLineItem->getLineItem();
    }

    #[\Override]
    protected function getProductLineItemPrice(CustomizeLoadedDataContext $context): ?ProductLineItemPrice
    {
        $productLineItemPrice = parent::getProductLineItemPrice($context);
        if ($productLineItemPrice instanceof ProductKitLineItemPrice) {
            $data = $context->getData();
            $config = $context->getConfig();
            $kitItemFieldName = $config->findFieldNameByPropertyPath('kitItem');
            $kitItemIdFieldName = $this->getAssociationIdFieldName($config, 'kitItem');
            $kitItemId = $data[$kitItemFieldName][$kitItemIdFieldName] ?? null;

            return $productLineItemPrice->getKitItemLineItemPrices()[$kitItemId] ?? null;
        }

        return null;
    }

    private function getAssociationIdFieldName(EntityDefinitionConfig $config, string $associationName): string
    {
        $ids = $config->getField($associationName)->getTargetEntity()->getIdentifierFieldNames();

        return $ids[0];
    }
}
