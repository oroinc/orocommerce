<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * Remove parent product in shopping list line item if product variant was removed
 */
class ProductVariantRemoveListener
{
    public function __construct(
        private DoctrineHelper $doctrineHelper,
    ) {
    }

    public function postRemove(ProductVariantLink $productVariantLink)
    {
        $repository = $this->doctrineHelper->getEntityRepository(LineItem::class);
        $repository->unsetRemovedProductVariant($productVariantLink);
    }
}
