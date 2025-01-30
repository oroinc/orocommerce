<?php

namespace Oro\Bundle\OrderBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Extend\Entity\Autocomplete\OroOrderBundle_Entity_OrderLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemChecksumAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;

/**
 * Represents ordered item.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @mixin OroOrderBundle_Entity_OrderLineItem
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_order_line_item')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-list-alt'],
        'security' => ['type' => 'ACL', 'group_name' => 'commerce', 'category' => 'orders']
    ]
)]
class OrderLineItem implements
    OrderHolderInterface,
    ProductLineItemInterface,
    ProductLineItemChecksumAwareInterface,
    ProductKitItemLineItemsAwareInterface,
    PriceAwareInterface,
    PriceTypeAwareInterface,
    ShippingAwareInterface,
    ProductLineItemsHolderAwareInterface,
    ExtendEntityInterface
{
    use DatesAwareTrait;
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToMany(targetEntity: Order::class, inversedBy: 'lineItems')]
    #[ORM\JoinTable(name: 'oro_order_line_items')]
    #[ORM\JoinColumn(name: 'line_item_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'order_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $orders = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'parent_product_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Product $parentProduct = null;

    #[ORM\Column(name: 'product_sku', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $productSku = null;

    #[ORM\Column(name: 'product_name', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $productName = null;

    /**
     * @var array
     */
    #[ORM\Column(name: 'product_variant_fields', type: Types::ARRAY, nullable: true)]
    protected $productVariantFields = [];

    #[ORM\Column(name: 'free_form_product', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $freeFormProduct = null;

    /**
     * @var float|null
     */
    #[ORM\Column(name: 'quantity', type: Types::FLOAT, nullable: true)]
    protected $quantity;

    #[ORM\ManyToOne(targetEntity: ProductUnit::class)]
    #[ORM\JoinColumn(name: 'product_unit_id', referencedColumnName: 'code', onDelete: 'SET NULL')]
    protected ?ProductUnit $productUnit = null;

    #[ORM\Column(name: 'product_unit_code', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $productUnitCode = null;

    #[ORM\Column(name: 'value', type: 'money', nullable: true)]
    protected ?float $value = null;

    #[ORM\Column(name: 'currency', type: Types::STRING, nullable: true)]
    protected ?string $currency = null;

    protected ?Price $price = null;

    /**
     * @var int
     */
    #[ORM\Column(name: 'price_type', type: Types::INTEGER)]
    protected $priceType = self::PRICE_TYPE_UNIT;

    #[ORM\Column(name: 'ship_by', type: Types::DATE_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $shipBy = null;

    #[ORM\Column(name: 'from_external_source', type: Types::BOOLEAN)]
    protected ?bool $fromExternalSource = false;

    #[ORM\Column(name: 'comment', type: Types::TEXT, nullable: true)]
    protected ?string $comment = null;

    /**
     * @var bool
     */
    protected $requirePriceRecalculation = false;

    #[ORM\Column(name: 'shipping_method', type: Types::STRING, nullable: true)]
    protected ?string $shippingMethod = null;

    #[ORM\Column(name: 'shipping_method_type', type: Types::STRING, nullable: true)]
    protected ?string $shippingMethodType = null;

    /**
     * @var float
     */
    #[ORM\Column(name: 'shipping_estimate_amount', type: 'money', nullable: true)]
    protected $shippingEstimateAmount;

    /**
     * @var Collection<int, OrderProductKitItemLineItem>
     */
    #[ORM\OneToMany(
        mappedBy: 'lineItem',
        targetEntity: OrderProductKitItemLineItem::class,
        cascade: ['ALL'],
        orphanRemoval: true,
        indexBy: 'kitItemId'
    )]
    #[OrderBy(['sortOrder' => Criteria::ASC])]
    protected ?Collection $kitItemLineItems = null;

    /**
     * Differentiates the unique constraint allowing to add the same product with the same unit code multiple times,
     * moving the logic of distinguishing of such line items out of the entity class.
     */
    #[ORM\Column(name: 'checksum', type: Types::STRING, length: 40, nullable: false, options: ['default' => ''])]
    protected ?string $checksum = '';

    public function __construct()
    {
        $this->kitItemLineItems = new ArrayCollection();
        $this->orders = new ArrayCollection();
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->productSku;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function addOrder(Order $order): self
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
        }

        return $this;
    }

    public function removeOrder(Order $order): self
    {
        if ($this->orders->contains($order)) {
            $this->orders->removeElement($order);
        }

        return $this;
    }

    #[\Override]
    public function getOrder(): ?Order
    {
        foreach ($this->orders as $order) {
            if (!$order->getSubOrders()->isEmpty()) {
                return $order;
            }
        }

        if ($this->orders->count()) {
            return $this->orders->first();
        }

        return null;
    }

    public function getOrders(): ?Collection
    {
        return $this->orders;
    }

    /**
     * Set product
     *
     * @param Product|null $product
     * @return $this
     */
    public function setProduct(?Product $product = null)
    {
        if ($product && (!$this->product || $product->getId() !== $this->product->getId())) {
            $this->requirePriceRecalculation = true;
        }

        $this->product = $product;

        return $this;
    }

    #[\Override]
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return Product
     */
    #[\Override]
    public function getParentProduct()
    {
        return $this->parentProduct;
    }

    /**
     * @param Product $parentProduct
     *
     * @return $this
     */
    public function setParentProduct(Product $parentProduct)
    {
        $this->parentProduct = $parentProduct;
        return $this;
    }

    /**
     * Set productSku
     *
     * @param string $productSku
     * @return $this
     */
    public function setProductSku($productSku)
    {
        $this->productSku = $productSku;

        return $this;
    }

    #[\Override]
    public function getProductSku()
    {
        return $this->productSku;
    }

    /**
     * @return string
     */
    public function getProductName()
    {
        return (string)$this->productName;
    }

    /**
     * @param string $productName
     *
     * @return $this
     */
    public function setProductName($productName)
    {
        $this->productName = $productName;

        return $this;
    }

    /**
     * @return array
     */
    public function getProductVariantFields()
    {
        return (array)$this->productVariantFields;
    }

    public function setProductVariantFields(?array $productVariantFields = null)
    {
        $this->productVariantFields = $productVariantFields;
    }

    /**
     * @return string
     */
    public function getFreeFormProduct()
    {
        return $this->freeFormProduct;
    }

    /**
     * @param string $freeFormProduct
     * @return $this
     */
    public function setFreeFormProduct($freeFormProduct)
    {
        $this->freeFormProduct = $freeFormProduct;

        return $this;
    }

    /**
     * Set quantity
     *
     * @param float $quantity
     * @return $this
     */
    public function setQuantity($quantity)
    {
        if ($quantity !== $this->quantity) {
            $this->requirePriceRecalculation = true;
        }

        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return float
     */
    #[\Override]
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set productUnit
     *
     * @param ProductUnit|null $productUnit
     * @return $this
     */
    public function setProductUnit(?ProductUnit $productUnit = null)
    {
        if ($productUnit && (!$this->productUnit || $productUnit->getCode() !== $this->productUnit->getCode())) {
            $this->requirePriceRecalculation = true;
        }

        $this->productUnit = $productUnit;

        return $this;
    }

    #[\Override]
    public function getProductUnit()
    {
        return $this->productUnit;
    }

    /**
     * Set productUnitCode
     *
     * @param string $productUnitCode
     * @return $this
     */
    public function setProductUnitCode($productUnitCode)
    {
        $this->productUnitCode = $productUnitCode;

        return $this;
    }

    #[\Override]
    public function getProductUnitCode()
    {
        return $this->productUnitCode;
    }

    public function setPrice(?Price $price = null): self
    {
        $this->price = $price;
        $this->updatePrice();

        return $this;
    }

    #[\Override]
    public function getPrice(): ?Price
    {
        return $this->price;
    }

    /**
     * Set priceType
     *
     * @param int $priceType
     * @return $this
     */
    public function setPriceType($priceType)
    {
        $this->priceType = $priceType;

        return $this;
    }

    /**
     * Get priceType
     *
     * @return int
     */
    #[\Override]
    public function getPriceType()
    {
        return $this->priceType;
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

    /**
     * @return boolean
     */
    public function isFromExternalSource()
    {
        return $this->fromExternalSource;
    }

    /**
     * @param boolean $fromExternalSource
     * @return $this
     */
    public function setFromExternalSource($fromExternalSource)
    {
        $this->fromExternalSource = (bool)$fromExternalSource;

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

    /**
     * Set seller comment
     *
     * @param string $comment
     * @return $this
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get seller comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @return \DateTime
     */
    public function getShipBy()
    {
        return $this->shipBy;
    }

    /**
     * @param \DateTime|null $shipBy
     * @return $this
     */
    public function setShipBy(?\DateTime $shipBy = null)
    {
        $this->shipBy = $shipBy;

        return $this;
    }

    public function getShippingMethod(): ?string
    {
        return $this->shippingMethod;
    }

    public function setShippingMethod(?string $shippingMethod): OrderLineItem
    {
        $this->shippingMethod = $shippingMethod;

        return $this;
    }

    public function getShippingMethodType(): ?string
    {
        return $this->shippingMethodType;
    }

    public function setShippingMethodType(?string $shippingMethodType): OrderLineItem
    {
        $this->shippingMethodType = $shippingMethodType;

        return $this;
    }

    public function getShippingEstimateAmount(): ?float
    {
        return $this->shippingEstimateAmount;
    }

    public function setShippingEstimateAmount(?float $shippingEstimateAmount): OrderLineItem
    {
        $this->shippingEstimateAmount = $shippingEstimateAmount;

        return $this;
    }

    #[\Override]
    public function getShippingCost(): ?Price
    {
        $amount = $this->shippingEstimateAmount;

        if (null !== $amount && $this->currency) {
            return Price::create($amount, $this->currency);
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isRequirePriceRecalculation()
    {
        return $this->requirePriceRecalculation;
    }

    #[ORM\PostLoad]
    public function createPrice()
    {
        if (null !== $this->value && null !== $this->currency) {
            $this->price = Price::create($this->value, $this->currency);
        }
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function preSave()
    {
        $this->updatePrice();
        $this->updateItemInformation();
    }

    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;
    }

    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

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

    protected function updateItemInformation()
    {
        $product = $this->getProduct();

        if ($product) {
            $this->productSku = $product->getSku();

            if ($this->getParentProduct()) {
                $product = $this->getParentProduct();
            }

            $this->productName = $product->getDenormalizedDefaultName();
        }

        if ($this->getProductUnit()) {
            $this->productUnitCode = $this->getProductUnit()->getCode();
        }
    }

    #[\Override]
    public function getEntityIdentifier()
    {
        return $this->id;
    }

    #[\Override]
    public function getProductHolder()
    {
        return $this;
    }

    #[\Override]
    public function getLineItemsHolder(): ?ProductLineItemsHolderInterface
    {
        return $this->getOrder();
    }

    /**
     * @return Collection<OrderProductKitItemLineItem>
     */
    #[\Override]
    public function getKitItemLineItems()
    {
        return $this->kitItemLineItems;
    }

    public function addKitItemLineItem(OrderProductKitItemLineItem $productKitItemLineItem): self
    {
        $index = $productKitItemLineItem->getKitItemId();

        if (!$this->kitItemLineItems->containsKey($index)) {
            $productKitItemLineItem->setLineItem($this);
            if ($index) {
                $this->kitItemLineItems->set($index, $productKitItemLineItem);
            } else {
                $this->kitItemLineItems->add($productKitItemLineItem);
            }
        }

        return $this;
    }

    public function removeKitItemLineItem(OrderProductKitItemLineItem $productKitItemLineItem): self
    {
        $this->kitItemLineItems->removeElement($productKitItemLineItem);

        return $this;
    }

    public function setChecksum(string $checksum): self
    {
        $this->checksum = $checksum;

        return $this;
    }

    #[\Override]
    public function getChecksum(): string
    {
        return $this->checksum;
    }
}
