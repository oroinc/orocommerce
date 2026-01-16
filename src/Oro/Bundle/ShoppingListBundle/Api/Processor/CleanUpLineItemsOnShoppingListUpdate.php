<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Removes line items from a shopping list that are not present
 * in the update request payload.
 */
class CleanUpLineItemsOnShoppingListUpdate implements ProcessorInterface
{
    public function __construct(
        private readonly ManagerRegistry $registry
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        $ids = $context->getSharedData()->get(SetUpdateLineItemsIdsSharedData::UPDATE_LINE_ITEMS_IDS);
        if (!$ids) {
            return;
        }

        /** @var CustomizeFormDataContext $context */

        $em = $this->registry->getManager();
        $entity = $context->getData();
        foreach ($entity->getLineItems()->getSnapshot() as $lineItem) {
            if (\in_array($lineItem->getId(), $ids, true)) {
                continue;
            }

            $em->remove($lineItem);
        }
    }
}
