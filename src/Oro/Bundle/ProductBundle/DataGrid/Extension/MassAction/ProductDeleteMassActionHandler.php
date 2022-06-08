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
     * {@inheritdoc}
     *
     * @param Product $entity
     */
    protected function isDeleteAllowed($entity)
    {
        if ($this->authorizationChecker->isGranted('DELETE', $entity)) {
            return !$this->registry->getRepository(ProductKitItem::class)->findProductKitsSkuByProduct($entity, 1);
        }

        return false;
    }
}
