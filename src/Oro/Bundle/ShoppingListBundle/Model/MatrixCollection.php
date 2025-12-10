<?php

namespace Oro\Bundle\ShoppingListBundle\Model;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Matrix form data holder
 */
class MatrixCollection
{
    /**
     * @var array|MatrixCollectionRow[]
     */
    #[Assert\Valid]
    public $rows = [];

    /**
     * @var ProductUnit
     */
    public $unit;

    public $columns = [];

    public $dimensions = 0;

    /** @var Product $product */
    public $product;

    /**
     * @var ShoppingList $shoppingList
     */
    public $shoppingList;

    public function hasLineItems()
    {
        foreach ($this->rows as $row) {
            foreach ($row->columns as $column) {
                if ($column?->quantity && $column?->quantity > 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
