<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;

/**
 * Represents a line item of the product kit item.
 *
 * @ORM\Table(
 *      name="oro_shopping_list_product_kit_item_line_item"
 * )
 * @ORM\Entity()
 * @Config(
 *      defaultValues={
 *          "dataaudit"={
 *              "auditable"=true
 *          }
 *      }
 * )
 */
class ProductKitItemLineItem implements ExtendEntityInterface, ProductKitItemLineItemInterface
{
    use ExtendEntityTrait;

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="LineItem", inversedBy="kitItemLineItems")
     * @ORM\JoinColumn(name="line_item_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected ?LineItem $lineItem = null;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\ProductKitItem")
     * @ORM\JoinColumn(name="product_kit_item_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected ?ProductKitItem $kitItem = null;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected ?Product $product = null;

    /**
     * @ORM\Column(name="quantity", type="float", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected ?float $quantity = null;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\ProductUnit")
     * @ORM\JoinColumn(name="unit_code", referencedColumnName="code", onDelete="CASCADE", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected ?ProductUnit $unit = null;

    /**
     * @ORM\Column(name="sort_order", type="integer", options={"default"=0}, nullable=false)
     */
    protected int $sortOrder = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntityIdentifier(): ?int
    {
        return $this->id;
    }

    public function setLineItem(?LineItem $lineItem): self
    {
        $this->lineItem = $lineItem;

        return $this;
    }

    public function getLineItem(): ?LineItem
    {
        return $this->lineItem;
    }

    public function setKitItem(?ProductKitItem $kitItem): self
    {
        $this->kitItem = $kitItem;

        return $this;
    }

    public function getKitItem(): ?ProductKitItem
    {
        return $this->kitItem;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function getProductSku(): ?string
    {
        return $this->getProduct()?->getSku();
    }

    public function getParentProduct(): ?Product
    {
        return null;
    }

    public function getProductHolder(): self
    {
        return $this;
    }

    public function setQuantity(?float $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function setUnit(?ProductUnit $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    public function getUnit(): ?ProductUnit
    {
        return $this->unit;
    }

    public function getProductUnit(): ?ProductUnit
    {
        return $this->getUnit();
    }

    public function getProductUnitCode(): ?string
    {
        return $this->getUnit()?->getCode();
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
}
