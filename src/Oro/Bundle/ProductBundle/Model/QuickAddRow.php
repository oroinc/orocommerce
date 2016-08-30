<?php

namespace Oro\Bundle\ProductBundle\Model;

use Oro\Bundle\ProductBundle\Entity\Product;

class QuickAddRow
{
    /**
     * @var int
     */
    protected $index;

    /**
     * @var string
     */
    protected $sku;

    /**
     * @var float
     */
    protected $quantity;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var bool
     */
    protected $valid = false;

    /**
     * @var bool
     */
    protected $complete = false;

    /**
     * @param int $index
     * @param string $sku
     * @param float $quantity
     */
    public function __construct($index, $sku, $quantity)
    {
        $this->index = $index;
        $this->sku = $sku;
        $this->quantity = $quantity;

        if ($sku && $quantity) {
            $this->complete = true;
        }
    }

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @return string
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * @return float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * @param bool $valid
     */
    public function setValid($valid)
    {
        $this->valid = $valid;
    }

    /**
     * @return bool
     */
    public function isComplete()
    {
        return $this->complete;
    }
}
