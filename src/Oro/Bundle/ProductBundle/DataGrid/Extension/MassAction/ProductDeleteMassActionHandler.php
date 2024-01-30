<?php

namespace Oro\Bundle\ProductBundle\DataGrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\MassAction\DeleteMassActionHandler;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;

/**
 * Delete mass action handler for Product entity. Postpones the product if a product is referenced by product kit items.
 */
class ProductDeleteMassActionHandler extends DeleteMassActionHandler
{
    protected function isPostponed(object $entity): bool
    {
        return $entity->isSimple() &&
            $this->registry->getRepository(ProductKitItem::class)->findProductKitsSkuByProduct($entity, 1);
    }
}
