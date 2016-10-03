<?php

namespace Oro\Bundle\InventoryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\InventoryBundle\Model\ExtendInventoryLevel;

/**
 * @ORM\Table(
 *     name="oro_inventory_level",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="uidx_oro_inventory_lev",
 *              columns={"product_unit_precision_id"}
 *          )
 *      }
 * )
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 */
class InventoryLevel extends ExtendInventoryLevel
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
     * @ORM\Column(name="quantity", type="decimal", precision=20, scale=10, nullable=false))
     */
    protected $quantity = 0;

    /**
     * @var Product $product
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $product;

    /**
     * @var ProductUnitPrecision $productUnitPrecision
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision")
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
     * @return InventoryLevel
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

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
     * @return ProductUnitPrecision
     */
    public function getProductUnitPrecision()
    {
        return $this->productUnitPrecision;
    }

    /**
     * @param ProductUnitPrecision $productUnitPrecision
     * @return InventoryLevel
     */
    public function setProductUnitPrecision(ProductUnitPrecision $productUnitPrecision)
    {
        $this->productUnitPrecision = $productUnitPrecision;
        $this->product = $productUnitPrecision->getProduct();

        return $this;
    }
}
