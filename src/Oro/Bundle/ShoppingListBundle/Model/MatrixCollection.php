<?php

namespace Oro\Bundle\ShoppingListBundle\Model;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Symfony\Component\Validator\Constraints as Assert;

class MatrixCollection
{
    /**
     * @Assert\Valid
     * @var array|MatrixCollectionRow[]
     */
    public $rows = [];

    /**
     * @var ProductUnit
     */
    public $unit;

    public function hasLineItems()
    {
        foreach ($this->rows as $row) {
            foreach ($row->columns as $column) {
                if ($column->quantity > 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
