<?php

namespace OroB2B\Bundle\WarehouseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\WarehouseBundle\Model\ExtendWarehouseInventoryLevel;

/**
 * @ORM\table(
 *     name="orob2b_warehouse_inventory_level"
 * )
 */
class WarehouseInventoryLevel extends ExtendWarehouseInventoryLevel
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var float
     *
     * @ORM\Column(name="quantity", type="decimal", scale=10, nullable=false))
     */
    protected $quantity;

    /**
     * @var Warehouse $warehouse
     *
     * @ORM\ManyToOne(targetEntity="Warehouse")
     * @ORM\JoinColumn(name="warehouse_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $warehouse;

    /**
     * @var Product $product
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $product;

    /**
     * @var ProductUnitPrecision $productUnitPrecision
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision")
     * @ORM\JoinColumn(name="product_unit_precision_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $productUnitPrecision;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param float $quantity
     * @return WarehouseInventoryLevel
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getWarehouse()
    {
        return $this->warehouse;
    }

    /**
     * @param Warehouse $warehouse
     * @return WarehouseInventoryLevel
     */
    public function setWarehouse(Warehouse $warehouse)
    {
        $this->warehouse = $warehouse;

        return $this;
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
     * @return WarehouseInventoryLevel
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return ProductUnitPrecision
     */
    public function getProductUnitPrecision()
    {
        return $this->productUnitPrecision;
    }

    /**
     * @param ProductUnitPrecision $productUnitPrecision
     * @return WarehouseInventoryLevel
     */
    public function setProductUnitPrecision(ProductUnitPrecision $productUnitPrecision)
    {
        $this->productUnitPrecision = $productUnitPrecision;

        return $this;
    }
}
