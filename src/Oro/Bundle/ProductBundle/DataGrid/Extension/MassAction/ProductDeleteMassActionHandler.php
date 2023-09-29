<?php

namespace Oro\Bundle\ProductBundle\DataGrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\MassAction\DeleteMassActionHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;

/**
 * Delete mass action handler for Product entity. Skips the product if a product is referenced by product kit items.
 */
class ProductDeleteMassActionHandler extends DeleteMassActionHandler
{
    /**
     * {@inheritDoc}
     */
    protected function isDeleteAllowed(object $entity): bool
    {
        /** @var Product $entity */
        if ($this->authorizationChecker->isGranted('DELETE', $entity)) {
            return !$this->registry->getRepository(ProductKitItem::class)->findProductKitsSkuByProduct($entity, 1);
        }

        return false;
    }
}
