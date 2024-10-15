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

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'lineItems')]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Order $order = null;

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

    /**
     * @var float
     */
    #[ORM\Column(name: 'value', type: 'money', nullable: true)]
    protected $value;

    #[ORM\Column(name: 'currency', type: Types::STRING, nullable: true)]
    protected ?string $currency = null;

    /**
     * @var Price
     */
    protected $price;

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
    }

    /**
     * @return string
     */
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

    public function setOrder(Order $order = null): self
    {
        $this->order = $order;

        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    /**
     * Set product
     *
     * @param Product|null $product
     * @return $this
     */
    public function setProduct(Product $product = null)
    {
        if ($product && (!$this->product || $product->getId() !== $this->product->getId())) {
            $this->requirePriceRecalculation = true;
        }

        $this->product = $product;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return Product
     */
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

    /**
     * {@inheritdoc}
     */
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

    public function setProductVariantFields(array $productVariantFields = null)
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
    public function setProductUnit(ProductUnit $productUnit = null)
    {
        if ($productUnit && (!$this->productUnit || $productUnit->getCode() !== $this->productUnit->getCode())) {
            $this->requirePriceRecalculation = true;
        }

        $this->productUnit = $productUnit;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getProductUnitCode()
    {
        return $this->productUnitCode;
    }

    /**
     * Set price
     *
     * @param Price|null $price
     * @return $this
     */
    public function setPrice(Price $price = null)
    {
        $this->price = $price;

        $this->updatePrice();

        return $this;
    }

    /**
     * Get price
     *
     * @return Price|null
     */
    public function getPrice()
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
    public function getPriceType()
    {
        return $this->priceType;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency)
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

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float $value
     * @return $this
     */
    public function setValue($value)
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
    public function setShipBy(\DateTime $shipBy = null)
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

    /**
     * {@inheritDoc}
     */
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

    public function updatePrice()
    {
        $this->value = $this->price ? $this->price->getValue() : null;
        $this->currency = $this->price ? $this->price->getCurrency() : null;
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

    /**
     * {@inheritdoc}
     */
    public function getEntityIdentifier()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductHolder()
    {
        return $this;
    }

    public function getLineItemsHolder(): ?ProductLineItemsHolderInterface
    {
        return $this->order;
    }

    /**
     * @return Collection<OrderProductKitItemLineItem>
     */
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

    public function getChecksum(): string
    {
        return $this->checksum;
    }
}
