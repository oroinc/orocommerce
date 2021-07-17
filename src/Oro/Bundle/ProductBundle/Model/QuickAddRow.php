<?php

namespace Oro\Bundle\ProductBundle\Model;

use Oro\Bundle\ProductBundle\Entity\Product;

class QuickAddRow
{
    use QuickAddFieldTrait;
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
     * @var string
     */
    protected $unit;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @param int $index
     * @param string $sku
     * @param float $quantity
     * @param string $unit
     */
    public function __construct($index, $sku, $quantity, $unit = null)
    {
        $this->index = $index;
        $this->sku = $sku;
        $this->quantity = $quantity;
        $this->unit = $unit;
        $this->errors = [];
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
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param string $unit
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;
    }

    /**
     * @param string $errorMessage
     * @param array $additionalParameters
     */
    public function addError($errorMessage, $additionalParameters = [])
    {
        $additionalParameters = array_merge($additionalParameters, [
            '{{ index }}' => $this->index,
            '{{ sku }}' => $this->sku
        ]);
        $this->errors[] = ['message' => $errorMessage, 'parameters' => $additionalParameters];
        $this->valid = false;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return count($this->errors) > 0;
    }
}
