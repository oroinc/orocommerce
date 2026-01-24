<?php

namespace Oro\Bundle\ShoppingListBundle\Model;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Represents a single cell (column) in a matrix order form for configurable products.
 *
 * This model holds the data for one cell in the matrix grid, which corresponds to a specific product variant.
 * Each column contains a label (typically the variant attribute value like "Red" or "Large"),
 * a reference to the actual product variant, and the quantity to be added to the shopping list.
 * This model is used as the data structure for the {@see MatrixColumnTyp} form type and
 * is part of the larger {@see MatrixCollection} hierarchy used for bulk ordering of product variants.
 */
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
