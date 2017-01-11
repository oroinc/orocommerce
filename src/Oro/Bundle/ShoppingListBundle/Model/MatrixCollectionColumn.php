<?php

namespace Oro\Bundle\ShoppingListBundle\Model;

use Oro\Bundle\ProductBundle\Entity\Product;

class MatrixCollectionColumn
{
    /**
     * @var string
     */
    public $label;

    /**
     * @var Product
     */
    public $product;

    /**
     * @var int
     */
    public $quantity;
}
