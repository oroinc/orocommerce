<?php

namespace Oro\Bundle\CheckoutBundle\Entity;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemPriceAwareInterface;

/**
 * Represents a checkout line item of a product kit item.
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_checkout_product_kit_item_line_item')]
#[ORM\HasLifecycleCallbacks]
#[Config(mode: 'hidden')]
class CheckoutProductKitItemLineItem implements
    ProductKitItemLineItemPriceAwareInterface,
    ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CheckoutLineItem::class, inversedBy: 'kitItemLineItems')]
    #[ORM\JoinColumn(name: 'line_item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?CheckoutLineItem $lineItem = null;

    #[ORM\ManyToOne(targetEntity: ProductKitItem::class)]
    #[ORM\JoinColumn(name: 'product_kit_item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?ProductKitItem $kitItem = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Product $product = null;

    #[ORM\Column(name: 'quantity', type: Types::FLOAT, nullable: false)]
    protected float $quantity = 1;
    #[ORM\ManyToOne(targetEntity: ProductUnit::class)]
    #[ORM\JoinColumn(name: 'product_unit_id', referencedColumnName: 'code', nullable: false, onDelete: 'CASCADE')]
    protected ?ProductUnit $productUnit = null;

    #[ORM\Column(name: 'sort_order', type: Types::INTEGER, nullable: false, options: ['default' => 0])]
    protected ?int $sortOrder = 0;

    #[ORM\Column(name: 'value', type: 'money', nullable: true)]
    protected ?float $value = null;

    #[ORM\Column(name: 'currency', type: Types::STRING, nullable: true)]
    protected ?string $currency = null;

    /**
     * Holds flag to determine if price can be changed
     */
    #[ORM\Column(name: 'is_price_fixed', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $priceFixed = false;

    protected ?Price $price = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntityIdentifier(): ?int
    {
        return $this->id;
    }

    public function setLineItem(?CheckoutLineItem $lineItem): self
    {
        $this->lineItem = $lineItem;

        return $this;
    }

    public function getLineItem(): ?CheckoutLineItem
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

    public function setQuantity(float $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function setProductUnit(?ProductUnit $productUnit): self
    {
        $this->productUnit = $productUnit;

        return $this;
    }

    public function getProductUnit(): ?ProductUnit
    {
        return $this->productUnit;
    }

    public function getProductUnitCode(): ?string
    {
        return $this->getProductUnit()?->getCode();
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

    public function isPriceFixed(): bool
    {
        return $this->priceFixed;
    }

    public function setPriceFixed(bool $isPriceFixed): self
    {
        $this->priceFixed = $isPriceFixed;

        return $this;
    }

    public function getPrice(): ?Price
    {
        return $this->price;
    }

    public function setPrice(?Price $price = null): self
    {
        $this->price = $price;

        $this->updatePrice();

        return $this;
    }

    #[ORM\PostLoad]
    public function createPrice(): void
    {
        if (null !== $this->value && null !== $this->currency) {
            $this->price = Price::create($this->value, $this->currency);
        }
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updatePrice(): void
    {
        $this->value = $this->price?->getValue();
        $this->currency = $this->price?->getCurrency();
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;
        $this->createPrice();

        return $this;
    }

    public function getValue(): ?float
    {
        if ($this->value !== null) {
            try {
                return BigDecimal::of($this->value)->toFloat();
            } catch (MathException $e) {
            }
        }

        return $this->value;
    }

    public function setValue(?float $value): self
    {
        $this->value = $value;
        $this->createPrice();

        return $this;
    }
}
