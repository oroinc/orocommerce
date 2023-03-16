<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Junction entity between product kit item and product to enable sort order.
 *
 * @ORM\Entity()
 * @ORM\Table(name="oro_product_kit_item_product")
 * @Config()
 */
class ProductKitItemProduct implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="ProductKitItem", inversedBy="kitItemProducts")
     * @ORM\JoinColumn(name="product_kit_item_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected ?ProductKitItem $kitItem = null;

    /**
     * @ORM\ManyToOne(targetEntity="Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected ?Product $product = null;

    /**
     * @ORM\Column(name="sort_order", type="integer", options={"default"=0})
     */
    protected int $sortOrder = 0;

    /**
     * @ORM\ManyToOne(targetEntity="ProductUnitPrecision")
     * @ORM\JoinColumn(name="product_unit_precision_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected ?ProductUnitPrecision $productUnitPrecision = null;

    public function __toString(): string
    {
        return (string)$this->product;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKitItem(): ?ProductKitItem
    {
        return $this->kitItem;
    }

    public function setKitItem(ProductKitItem $productKitItem): self
    {
        $this->kitItem = $productKitItem;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function getProductUnitPrecision(): ?ProductUnitPrecision
    {
        return $this->productUnitPrecision;
    }

    public function setProductUnitPrecision(?ProductUnitPrecision $productUnitPrecision): self
    {
        $this->productUnitPrecision = $productUnitPrecision;

        return $this;
    }

    public function updateProductUnitPrecision(?string $unitCode = null): self
    {
        $unitCode = $unitCode ?? $this->kitItem?->getProductUnit()?->getCode();
        $this->setProductUnitPrecision($this->product?->getUnitPrecision((string) $unitCode));

        return $this;
    }
}
