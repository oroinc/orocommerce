<?php

namespace Oro\Bundle\CheckoutBundle\Entity;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemPriceAwareInterface;

/**
 * Represents a checkout line item of a product kit item.
 *
 * @ORM\Table(name="oro_checkout_product_kit_item_line_item")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      mode="hidden"
 * )
 */
class CheckoutProductKitItemLineItem implements
    ProductKitItemLineItemPriceAwareInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="CheckoutLineItem", inversedBy="kitItemLineItems")
     * @ORM\JoinColumn(name="line_item_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected ?CheckoutLineItem $lineItem = null;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\ProductKitItem")
     * @ORM\JoinColumn(name="product_kit_item_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected ?ProductKitItem $kitItem = null;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected ?Product $product = null;

    /**
     * @ORM\Column(name="quantity", type="float", nullable=false)
     */
    protected float $quantity = 1;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\ProductUnit")
     * @ORM\JoinColumn(name="unit_code", referencedColumnName="code", onDelete="CASCADE", nullable=false)
     */
    protected ?ProductUnit $unit = null;

    /**
     * @ORM\Column(name="sort_order", type="integer", options={"default"=0}, nullable=false)
     */
    protected int $sortOrder = 0;

    /**
     * @ORM\Column(name="value", type="money", nullable=true)
     */
    protected ?float $value = null;

    /**
     * @ORM\Column(name="currency", type="string", nullable=true)
     */
    protected ?string $currency = null;

    /**
     * Holds flag to determine if price can be changed
     *
     * @ORM\Column(name="is_price_fixed", type="boolean", options={"default"=false})
     */
    protected bool $priceFixed = false;

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

    /**
     * @ORM\PostLoad
     */
    public function createPrice(): void
    {
        if (null !== $this->value && null !== $this->currency) {
            $this->price = Price::create($this->value, $this->currency);
        }
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
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
