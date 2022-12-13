<?php

namespace Oro\Bundle\ProductBundle\Model;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * A model that represents a row in {@see QuickAddRowCollection}.
 */
class QuickAddRow implements ProductHolderInterface, QuantityAwareInterface
{
    public const INDEX = 'index';
    public const SKU = 'sku';
    public const UNIT = 'unit';
    public const QUANTITY = 'quantity';

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
     * @var string
     */
    protected $unit;

    /**
     * @var string
     */
    protected $organization;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @param int $index
     * @param string $sku
     * @param float $quantity
     * @param string $unit
     * @param string $organization
     */
    public function __construct($index, $sku, $quantity, $unit = null, $organization = null)
    {
        $this->index = $index;
        $this->sku = $sku;
        $this->quantity = $quantity;
        $this->unit = $unit;
        $this->organization = $organization;
        $this->errors = [];
    }

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    public function getEntityIdentifier(): ?int
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

    public function getProductSku(): ?string
    {
        return $this->product?->getSku();
    }

    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    public function setOrganization(string $organization): void
    {
        $this->organization = $organization;
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

    public function addError(string $errorMessage, array $additionalParameters = [], string $propertyPath = ''): void
    {
        if (count(func_get_args()) > 2) {
            $propertyPath = (string) func_get_arg(2);
        }

        $additionalParameters = array_merge($additionalParameters, [
            '{{ index }}' => $this->index,
            '{{ sku }}' => $this->sku
        ]);
        $this->errors[] = [
            'message' => $errorMessage,
            'parameters' => $additionalParameters,
            'propertyPath' => $propertyPath ?? '',
        ];
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
