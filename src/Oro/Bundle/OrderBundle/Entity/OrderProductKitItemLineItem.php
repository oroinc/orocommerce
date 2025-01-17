<?php

namespace Oro\Bundle\OrderBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemPriceAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductUnitPrecisionAwareInterface;

/**
 * Represents an order line item of a product kit item.
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_order_product_kit_item_line_item')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-list-alt'],
        'security' => ['type' => 'ACL', 'group_name' => 'commerce', 'category' => 'orders']
    ]
)]
class OrderProductKitItemLineItem implements
    OrderHolderInterface,
    ProductHolderInterface,
    ProductKitItemLineItemPriceAwareInterface,
    ProductUnitPrecisionAwareInterface,
    ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: OrderLineItem::class, inversedBy: 'kitItemLineItems')]
    #[ORM\JoinColumn(name: 'line_item_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?OrderLineItem $lineItem = null;

    #[ORM\ManyToOne(targetEntity: ProductKitItem::class)]
    #[ORM\JoinColumn(name: 'product_kit_item_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?ProductKitItem $kitItem = null;

    #[ORM\Column(name: 'product_kit_item_id_fallback', type: Types::INTEGER, nullable: false)]
    protected ?int $kitItemId = null;

    #[ORM\Column(name: 'product_kit_item_label', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $kitItemLabel = null;

    #[ORM\Column(name: 'optional', type: Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $optional = false;

    #[ORM\Column(name: 'minimum_quantity', type: Types::FLOAT, nullable: true)]
    protected ?float $minimumQuantity = null;

    #[ORM\Column(name: 'maximum_quantity', type: Types::FLOAT, nullable: true)]
    protected ?float $maximumQuantity = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?Product $product = null;

    #[ORM\Column(name: 'product_id_fallback', type: Types::INTEGER, nullable: false)]
    protected ?int $productId = null;

    #[ORM\Column(name: 'product_sku', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $productSku = null;

    #[ORM\Column(name: 'product_name', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $productName = null;

    #[ORM\Column(name: 'quantity', type: Types::FLOAT, nullable: false)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?float $quantity = 1;

    #[ORM\ManyToOne(targetEntity: ProductUnit::class)]
    #[ORM\JoinColumn(name: 'product_unit_id', referencedColumnName: 'code', nullable: true, onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?ProductUnit $productUnit = null;

    #[ORM\Column(name: 'product_unit_code', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $productUnitCode = null;

    #[ORM\Column(name: 'product_unit_precision', type: Types::INTEGER, nullable: false)]
    protected ?int $productUnitPrecision = 0;

    #[ORM\Column(name: 'sort_order', type: Types::INTEGER, nullable: false, options: ['default' => 0])]
    protected ?int $sortOrder = 0;

    #[ORM\Column(name: 'value', type: 'money', nullable: true)]
    protected ?float $value = null;

    #[ORM\Column(name: 'currency', type: Types::STRING, nullable: true)]
    protected ?string $currency = null;

    protected ?Price $price = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    #[\Override]
    public function getEntityIdentifier(): ?int
    {
        return $this->id;
    }

    public function setLineItem(?OrderLineItem $lineItem): self
    {
        $this->lineItem = $lineItem;

        return $this;
    }

    #[\Override]
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

    #[\Override]
    public function getKitItem(): ?ProductKitItem
    {
        return $this->kitItem;
    }

    public function getKitItemId(): ?int
    {
        return $this->kitItemId;
    }

    public function setKitItemId(?int $kitItemId): self
    {
        $this->kitItemId = $kitItemId;

        return $this;
    }

    public function setKitItemLabel(string $kitItemLabel): self
    {
        $this->kitItemLabel = $kitItemLabel;

        return $this;
    }

    public function getKitItemLabel(): ?string
    {
        return $this->kitItemLabel;
    }

    public function setOptional(bool $optional): self
    {
        $this->optional = $optional;

        return $this;
    }

    public function isOptional(): bool
    {
        return $this->optional;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;
        $this->updateProductFallbackFields();

        return $this;
    }

    #[\Override]
    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function setProductId(?int $productId): self
    {
        $this->productId = $productId;

        return $this;
    }

    public function setProductSku(?string $productSku): self
    {
        $this->productSku = $productSku;

        return $this;
    }

    #[\Override]
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

    #[\Override]
    public function getParentProduct(): ?Product
    {
        return null;
    }

    #[\Override]
    public function getProductHolder(): self
    {
        return $this;
    }

    public function setQuantity(?float $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    #[\Override]
    public function getQuantity(): ?float
    {
        return $this->quantity;
    }

    public function getMinimumQuantity(): ?float
    {
        return $this->minimumQuantity;
    }

    public function setMinimumQuantity(?float $minimumQuantity): self
    {
        $this->minimumQuantity = $minimumQuantity;

        return $this;
    }

    public function getMaximumQuantity(): ?float
    {
        return $this->maximumQuantity;
    }

    public function setMaximumQuantity(?float $maximumQuantity): self
    {
        $this->maximumQuantity = $maximumQuantity;

        return $this;
    }

    public function setProductUnit(?ProductUnit $productUnit): self
    {
        $this->productUnit = $productUnit;
        $this->updateProductUnitFallbackFields();

        return $this;
    }

    #[\Override]
    public function getProductUnit(): ?ProductUnit
    {
        return $this->productUnit;
    }

    public function setProductUnitCode(?string $productUnitCode): self
    {
        $this->productUnitCode = $productUnitCode;

        return $this;
    }

    #[\Override]
    public function getProductUnitCode(): ?string
    {
        return $this->productUnitCode;
    }

    #[\Override]
    public function getProductUnitPrecision(): int
    {
        return $this->productUnitPrecision;
    }

    public function setProductUnitPrecision(int $productUnitPrecision): self
    {
        $this->productUnitPrecision = $productUnitPrecision;

        return $this;
    }

    #[\Override]
    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    #[\Override]
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
        if (null === $this->price) {
            $this->value = null;
            $this->currency = null;
        } else {
            $value = $this->price->getValue();
            if (null !== $value) {
                $this->value = (float)$value;
            }
            $currency = $this->price->getCurrency();
            if (null !== $currency) {
                $this->currency = (string)$currency;
            }
        }
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateFallbackFields(): void
    {
        if ($this->product !== null) {
            $this->updateProductFallbackFields();
        }

        if ($this->productUnit !== null) {
            $this->updateProductUnitFallbackFields();
        }

        if ($this->kitItem !== null) {
            $this->updateKitItemFallbackFields();
        }
    }

    protected function updateProductFallbackFields(): void
    {
        if ($this->product === null) {
            $this->productId = null;
            $this->productSku = null;
            $this->productName = null;

            return;
        }

        if ($this->productId === null || $this->product->getId() !== $this->productId) {
            $this->productId = $this->product->getId();
            $this->productSku = $this->product->getSku();
            $this->productName = $this->product->getDenormalizedDefaultName();

            if ($this->productUnitCode !== null) {
                $this->updateUnitPrecisionFallbackField();
            }
        }
    }

    protected function updateProductUnitFallbackFields(): void
    {
        if ($this->productUnit === null) {
            $this->productUnitCode = null;
            $this->productUnitPrecision = 0;

            return;
        }

        if ($this->productUnitCode === null || $this->productUnit->getCode() !== $this->productUnitCode) {
            $this->productUnitCode = $this->productUnit->getCode();

            if ($this->product !== null) {
                $this->updateUnitPrecisionFallbackField();
            }
        }
    }

    protected function updateUnitPrecisionFallbackField(): void
    {
        if ($this->product !== null && $this->productUnitCode !== null) {
            $precision = $this->product->getUnitPrecision($this->productUnitCode)?->getPrecision();
            $this->productUnitPrecision = $precision !== null
                ? (int)$precision
                : (int)$this->getProductUnit()?->getDefaultPrecision();
        }
    }

    protected function updateKitItemFallbackFields(): void
    {
        if ($this->kitItem === null) {
            $this->kitItemId = null;
            $this->kitItemLabel = null;
            $this->optional = false;
            $this->minimumQuantity = null;
            $this->maximumQuantity = null;

            return;
        }

        if ($this->kitItemId === null || $this->kitItem->getId() !== $this->kitItemId) {
            $this->kitItemId = $this->kitItem->getId();
            $this->kitItemLabel = $this->kitItem->getDefaultLabel()?->getString();
            $this->optional = $this->kitItem->isOptional();
            $this->minimumQuantity = $this->kitItem->getMinimumQuantity();
            $this->maximumQuantity = $this->kitItem->getMaximumQuantity();
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
        return $this->value;
    }

    public function setValue(?float $value): self
    {
        $this->value = $value;
        $this->createPrice();

        return $this;
    }

    #[\Override]
    public function getOrder(): ?Order
    {
        return $this->getLineItem()?->getOrder();
    }
}
