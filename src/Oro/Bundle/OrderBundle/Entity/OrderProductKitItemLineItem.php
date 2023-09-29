<?php

namespace Oro\Bundle\OrderBundle\Entity;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemPriceAwareInterface;

/**
 * Represents a checkout line item of a product kit item.
 *
 * @ORM\Table(name="oro_order_product_kit_item_line_item")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-list-alt"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce",
 *              "category"="orders"
 *          }
 *      }
 * )
 */
class OrderProductKitItemLineItem implements
    ProductKitItemLineItemPriceAwareInterface,
    ExtendEntityInterface
{
    use ExtendEntityTrait;

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="OrderLineItem", inversedBy="kitItemLineItems")
     * @ORM\JoinColumn(name="line_item_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected ?OrderLineItem $lineItem = null;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\ProductKitItem")
     * @ORM\JoinColumn(name="product_kit_item_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
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
     * @ORM\Column(name="product_kit_item_label", type="string", length=255, nullable=false)
     */
    protected ?string $kitItemLabel = null;

    /**
     * @ORM\Column(name="product_kit_item_optional", type="boolean", options={"default"=false})
     */
    protected bool $kitItemOptional = false;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
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
     * @ORM\Column(name="product_sku", type="string", length=255, nullable=false)
     */
    protected ?string $productSku = null;

    /**
     * @ORM\Column(name="product_name", type="string", length=255, nullable=false)
     */
    protected ?string $productName = null;

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
    protected ?float $quantity = 1;

    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\ProductUnit")
     * @ORM\JoinColumn(name="unit_code", referencedColumnName="code", onDelete="SET NULL", nullable=true)
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
     * @ORM\Column(name="product_unit_code", type="string", length=255, nullable=false)
     */
    protected ?string $unitCode = null;

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

    protected ?Price $price = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntityIdentifier(): ?int
    {
        return $this->id;
    }

    public function setLineItem(?OrderLineItem $lineItem): self
    {
        $this->lineItem = $lineItem;

        return $this;
    }

    public function getLineItem(): ?OrderLineItem
    {
        return $this->lineItem;
    }

    public function setKitItem(?ProductKitItem $kitItem): self
    {
        $this->kitItem = $kitItem;
        $this->updateKitItemFallbackFields();

        return $this;
    }

    public function getKitItem(): ?ProductKitItem
    {
        return $this->kitItem;
    }

    public function setKitItemLabel(?string $kitItemLabel): self
    {
        $this->kitItemLabel = $kitItemLabel;

        return $this;
    }

    public function getKitItemLabel(): ?string
    {
        return $this->kitItemLabel;
    }

    public function setKitItemOptional(bool $kitItemOptional): self
    {
        $this->kitItemOptional = $kitItemOptional;

        return $this;
    }

    public function isKitItemOptional(): bool
    {
        return $this->kitItemOptional;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;
        $this->updateProductFallbackFields();

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProductSku(?string $productSku): self
    {
        $this->productSku = $productSku;

        return $this;
    }

    public function getProductSku(): ?string
    {
        return $this->productSku;
    }

    public function setProductName(?string $productName): self
    {
        $this->productName = $productName;

        return $this;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
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
        $this->updateProductUnitFallbackFields();

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

    public function setUnitCode(?string $unitCode): self
    {
        $this->unitCode = $unitCode;

        return $this;
    }

    public function getUnitCode(): ?string
    {
        return $this->unitCode;
    }

    public function setProductUnitCode(?string $unitCode): self
    {
        return $this->setUnitCode($unitCode);
    }

    public function getProductUnitCode(): ?string
    {
        return $this->getUnitCode();
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

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateFallbackFields(): void
    {
        $this->updateProductFallbackFields();
        $this->updateProductUnitFallbackFields();
        $this->updateKitItemFallbackFields();
    }

    protected function updateProductFallbackFields(): void
    {
        $product = $this->getProduct();
        if ($product) {
            $this->productSku = $product->getSku();
            $this->productName = $product->getDenormalizedDefaultName();
        }
    }

    protected function updateProductUnitFallbackFields(): void
    {
        if ($this->getProductUnit()) {
            $this->unitCode = $this->getProductUnit()->getCode();
        }
    }

    protected function updateKitItemFallbackFields(): void
    {
        $kitItem = $this->getKitItem();
        if ($kitItem) {
            $this->kitItemLabel = $kitItem->getDefaultLabel()?->getString();
            $this->kitItemOptional = $kitItem->isOptional();
        }
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
