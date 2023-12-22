<?php

namespace Oro\Bundle\ProductBundle\DataGrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\MassAction\DeleteMassActionHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;

/**
 * Delete mass action handler for Product entity. Postpones the product if a product is referenced by product kit items.
 */
class ProductDeleteMassActionHandler extends DeleteMassActionHandler
{
    /**
     * {@inheritdoc}
     * @deprecated since 5.1
     *
     * @param Product $entity
     */
    protected function isDeleteAllowed($entity)
    {
        return $this->authorizationChecker->isGranted('DELETE', $entity);
    }

    protected function isPostponed(object $entity): bool
    {
        return $entity->isSimple() &&
            $this->registry->getRepository(ProductKitItem::class)->findProductKitsSkuByProduct($entity, 1);
    }
}
