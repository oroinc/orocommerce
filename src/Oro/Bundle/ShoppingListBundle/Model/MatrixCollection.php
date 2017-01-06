<?php

namespace Oro\Bundle\ShoppingListBundle\Model;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;

class MatrixCollection
{
    /**
     * @var array|MatrixCollectionRow[]
     */
    public $rows = [];

    /**
     * @var ProductUnit
     */
    public $unit;
}
