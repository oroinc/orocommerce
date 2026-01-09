<?php

namespace Oro\Bundle\ProductBundle\RelatedItem;

use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Defines the contract for repositories managing related item assignments.
 *
 * Implementations of this interface provide repository methods for checking the existence
 * of related item relationships and counting the number of relations for a product,
 * supporting both related products and upsell products functionality.
 */
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
