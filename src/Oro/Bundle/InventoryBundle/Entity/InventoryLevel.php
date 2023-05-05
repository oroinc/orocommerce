<?php

namespace Oro\Bundle\InventoryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

/**
 * Represents inventory level (the current amount of a product that a business has in stock)
 *
 * @ORM\Table(
 *     name="oro_inventory_level",
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository")
 * @Config(
 *      defaultValues={
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="organization",
 *              "owner_column_name"="organization_id"
 *          }
 *      }
 * )
 */
class InventoryLevel implements
    OrganizationAwareInterface,
    ExtendEntityInterface
{
    use OrganizationAwareTrait;
    use ExtendEntityTrait;

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
