<?php

namespace Oro\Bundle\OrderBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;

/**
 * Represents ordered item.
 *
 * @ORM\Table(name="oro_order_line_item")
 * @ORM\Entity
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
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class OrderLineItem implements
    ProductLineItemInterface,
    ProductKitItemLineItemsAwareInterface,
    PriceAwareInterface,
    PriceTypeAwareInterface,
    ShippingAwareInterface,
    ProductLineItemsHolderAwareInterface,
    ExtendEntityInterface
{
    use DatesAwareTrait;
    use ExtendEntityTrait;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrderBundle\Entity\Order", inversedBy="lineItems")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $order;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $product;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="parent_product_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $parentProduct;

    /**
     * @var string
     *
     * @ORM\Column(name="product_sku", type="string", length=255, nullable=true)
     */
    protected $productSku;

    /**
     * @var string|null
     *
     * @ORM\Column(name="product_name", type="string", length=255, nullable=true)
     */
    protected $productName;

    /**
     * @var array
     *
     * @ORM\Column(name="product_variant_fields", type="array", nullable=true)
     */
    protected $productVariantFields = [];

    /**
     * @var string
     *
     * @ORM\Column(name="free_form_product", type="string", length=255, nullable=true)
     */
    protected $freeFormProduct;

    /**
     * @var float
     *
     * @ORM\Column(name="quantity", type="float", nullable=true)
     */
    protected $quantity;

    /**
     * @var ProductUnit
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\ProductUnit")
     * @ORM\JoinColumn(name="product_unit_id", referencedColumnName="code", onDelete="SET NULL")
     */
    protected $productUnit;

    /**
     * @var string
     *
     * @ORM\Column(name="product_unit_code", type="string", length=255, nullable=true)
     */
    protected $productUnitCode;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="money", nullable=true)
     */
    protected $value;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", nullable=true)
     */
    protected $currency;

    /**
     * @var Price
     */
    protected $price;

    /**
     * @var int
     *
     * @ORM\Column(name="price_type", type="integer")
     */
    protected $priceType = self::PRICE_TYPE_UNIT;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ship_by", type="date", nullable=true)
     */
    protected $shipBy;

    /**
     * @var bool
     *
     * @ORM\Column(name="from_external_source", type="boolean")
     */
    protected $fromExternalSource = false;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected $comment;

    /**
     * @var bool
     */
    protected $requirePriceRecalculation = false;

    /**
     * @var string
     *
     * @ORM\Column(name="shipping_method", type="string", nullable=true)
     */
    protected $shippingMethod;

    /**
     * @var string
     *
     * @ORM\Column(name="shipping_method_type", type="string", nullable=true)
     */
    protected $shippingMethodType;

    /**
     * @var float
     *
     * @ORM\Column(name="shipping_estimate_amount", type="money", nullable=true)
     */
    protected $shippingEstimateAmount;

    /**
     * @var Collection<OrderProductKitItemLineItem>
     *
     * @ORM\OneToMany(
     *     targetEntity="OrderProductKitItemLineItem",
     *     mappedBy="lineItem",
     *     cascade={"ALL"},
     *     orphanRemoval=true,
     *     indexBy="kitItemId"
     * )
     * @OrderBy({"sortOrder"="ASC"})
     */
    protected $kitItemLineItems;

    /**
     * Differentiates the unique constraint allowing to add the same product with the same unit code multiple times,
     * moving the logic of distinguishing of such line items out of the entity class.
     *
     * @ORM\Column(name="checksum", type="string", length=40, options={"default"=""}, nullable=false)
     */
    protected string $checksum = '';

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

    /**
     * Set order
     *
     * @param Order|null $order
     * @return $this
     */
    public function setOrder(Order $order = null)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return Order
     */
    public function getOrder()
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

    /**
     * @ORM\PostLoad
     */
    public function createPrice()
    {
        if (null !== $this->value && null !== $this->currency) {
            $this->price = Price::create($this->value, $this->currency);
        }
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preSave()
    {
        $this->updatePrice();
        $this->updateItemInformation();
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;
    }

    /**
     * @ORM\PreUpdate
     */
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
