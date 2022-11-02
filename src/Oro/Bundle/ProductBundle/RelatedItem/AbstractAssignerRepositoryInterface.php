<?php

namespace Oro\Bundle\ProductBundle\RelatedItem;

use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\ProductBundle\Entity\Product;

interface AbstractAssignerRepositoryInterface extends ObjectRepository
{
    /**
     * @param Product|int $productFrom
     * @param Product|int $productTo
     * @return bool
     */
    public function exists($productFrom, $productTo);

    /**
     * @param int $id
     * @return int
     */
    public function countRelationsForProduct($id);
}
