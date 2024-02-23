<?php

namespace Oro\Bundle\RFPBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductUnitPrecisionAwareInterface;

/**
 * Represents an RFP request line item of a product kit item.
 *
 * @ORM\Table(name="oro_rfp_request_prod_kit_item_line_item")
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
 *              "category"="quotes"
 *          }
 *      }
 * )
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class RequestProductKitItemLineItem implements
    ProductKitItemLineItemInterface,
    ProductUnitPrecisionAwareInterface,
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
     * @ORM\ManyToOne(targetEntity="RequestProduct", inversedBy="kitItemLineItems")
     * @ORM\JoinColumn(name="request_product_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected ?RequestProduct $requestProduct = null;

    protected ?RequestProductItem $lineItem = null;

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
     * @ORM\Column(name="product_kit_item_id_fallback", type="integer", nullable=false)
     */
    protected ?int $kitItemId = null;

    /**
     * @ORM\Column(name="product_kit_item_label", type="string", length=255, nullable=false)
     */
    protected ?string $kitItemLabel = null;

    /**
     * @ORM\Column(name="optional", type="boolean", options={"default"=false})
     */
    protected ?bool $optional = false;

    /**
     * @ORM\Column(name="minimum_quantity", type="float", nullable=true)
     */
    protected ?float $minimumQuantity = null;

    /**
     * @ORM\Column(name="maximum_quantity", type="float", nullable=true)
     */
    protected ?float $maximumQuantity = null;

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
     * @ORM\Column(name="product_id_fallback", type="integer", nullable=false)
     */
    protected ?int $productId = null;

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
     * @ORM\JoinColumn(name="product_unit_id", referencedColumnName="code", onDelete="SET NULL", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected ?ProductUnit $productUnit = null;

    /**
     * @ORM\Column(name="product_unit_code", type="string", length=255, nullable=false)
     */
    protected ?string $productUnitCode = null;

    /**
     * @ORM\Column(name="product_unit_precision", type="integer", nullable=false)
     */
    protected int $productUnitPrecision = 0;

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

    public function getRequestProduct(): ?RequestProduct
    {
        return $this->requestProduct;
    }

    public function setRequestProduct(?RequestProduct $requestProduct): self
    {
        $this->requestProduct = $requestProduct;

        return $this;
    }

    public function setLineItem(?RequestProductItem $lineItem): self
    {
        $this->lineItem = $lineItem;

        return $this;
    }

    public function getLineItem(): ?RequestProductItem
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

    public function getProductUnit(): ?ProductUnit
    {
        return $this->productUnit;
    }

    public function setProductUnitCode(?string $productUnitCode): self
    {
        $this->productUnitCode = $productUnitCode;

        return $this;
    }

    public function getProductUnitCode(): ?string
    {
        return $this->productUnitCode;
    }

    public function setProductUnitPrecision(int $productUnitPrecision): self
    {
        $this->productUnitPrecision = $productUnitPrecision;

        return $this;
    }

    public function getProductUnitPrecision(): int
    {
        return $this->productUnitPrecision;
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

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
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
}
