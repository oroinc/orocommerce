<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * Computes values for "currency" and "value" fields for a shopping list item.
 */
class ComputeShoppingListLineItemPrice extends AbstractComputeLineItemPrice
{
    #[\Override]
    protected function getShoppingListLineItem(CustomizeLoadedDataContext $context): ?LineItem
    {
        $config = $context->getConfig();
        $idFieldName = $config->findFieldNameByPropertyPath('id');

        $entityManager = $this->managerRegistry->getManagerForClass(LineItem::class);
        $lineItem = $entityManager->getReference(LineItem::class, $context->getData()[$idFieldName]);
        if ($lineItem === null) {
            return null;
        }

        $entityManager->refresh($lineItem);

        return $lineItem;
    }
}
