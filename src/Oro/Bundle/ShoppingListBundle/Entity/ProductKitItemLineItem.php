<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;

/**
 * Represents a shopping list line item of a product kit item.
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_shopping_list_product_kit_item_line_item')]
#[Config(defaultValues: ['dataaudit' => ['auditable' => true]])]
class ProductKitItemLineItem implements ExtendEntityInterface, ProductKitItemLineItemInterface
{
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: LineItem::class, inversedBy: 'kitItemLineItems')]
    #[ORM\JoinColumn(name: 'line_item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?LineItem $lineItem = null;

    #[ORM\ManyToOne(targetEntity: ProductKitItem::class)]
    #[ORM\JoinColumn(name: 'product_kit_item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?ProductKitItem $kitItem = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?Product $product = null;

    #[ORM\Column(name: 'quantity', type: Types::FLOAT, nullable: false)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?float $quantity = 1;

    #[ORM\ManyToOne(targetEntity: ProductUnit::class)]
    #[ORM\JoinColumn(name: 'unit_code', referencedColumnName: 'code', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?ProductUnit $unit = null;

    #[ORM\Column(name: 'sort_order', type: Types::INTEGER, nullable: false, options: ['default' => 0])]
    protected ?int $sortOrder = 0;

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
