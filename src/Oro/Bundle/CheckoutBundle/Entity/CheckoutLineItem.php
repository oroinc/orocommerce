<?php

namespace Oro\Bundle\CheckoutBundle\Entity;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutLineItemRepository;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
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
 * Represents checkout item.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
#[ORM\Entity(repositoryClass: CheckoutLineItemRepository::class)]
#[ORM\Table(name: 'oro_checkout_line_item')]
#[ORM\HasLifecycleCallbacks]
#[Config(mode: 'hidden')]
class CheckoutLineItem implements
    PriceAwareInterface,
    PriceTypeAwareInterface,
    ProductLineItemInterface,
    ProductLineItemsHolderAwareInterface,
    ProductLineItemChecksumAwareInterface,
    ProductKitItemLineItemsAwareInterface,
    ShippingAwareInterface,
    ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Checkout::class, inversedBy: 'lineItems')]
    #[ORM\JoinColumn(name: 'checkout_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Checkout $checkout = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'parent_product_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Product $parentProduct = null;

    #[ORM\Column(name: 'product_sku', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $productSku = null;

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

    #[ORM\Column(name: 'price_type', type: Types::INTEGER)]
    protected ?int $priceType = self::PRICE_TYPE_UNIT;

    #[ORM\Column(name: 'from_external_source', type: Types::BOOLEAN)]
    protected ?bool $fromExternalSource = false;

    #[ORM\Column(name: 'comment', type: Types::TEXT, nullable: true)]
    protected ?string $comment = null;

    /**
     * Hold flag to determine if price can be changed
     *
     * @var bool
     */
    #[ORM\Column(name: 'is_price_fixed', type: Types::BOOLEAN)]
    protected ?bool $priceFixed = false;

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
     * @var Collection<int, CheckoutProductKitItemLineItem>
     */
    #[ORM\OneToMany(
        mappedBy: 'lineItem',
        targetEntity: CheckoutProductKitItemLineItem::class,
        cascade: ['ALL'],
        orphanRemoval: true
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
    #[\Override]
    public function __toString()
    {
        return (string)$this->productSku;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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

    /**
     * @return Checkout
     */
    public function getCheckout()
    {
        return $this->checkout;
    }

    /**
     * @param Checkout $checkout
     *
     * @return $this
     */
    public function setCheckout(Checkout $checkout)
    {
        $this->checkout = $checkout;

        return $this;
    }

    /**
     * @param Product|null $product
     *
     * @return $this
     */
    public function setProduct(Product $product = null)
    {
        $this->product = $product;

        return $this;
    }

    #[\Override]
    public function getProduct()
    {
        return $this->product;
    }

    #[\Override]
    public function getParentProduct()
    {
        return $this->parentProduct;
    }

    /**
     * @param Product|null $parentProduct
     *
     * @return $this
     */
    public function setParentProduct(Product $parentProduct = null)
    {
        $this->parentProduct = $parentProduct;

        return $this;
    }

    /**
     * @param string $productSku
     *
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
    public function getFreeFormProduct()
    {
        return $this->freeFormProduct;
    }

    /**
     * @param string $freeFormProduct
     *
     * @return $this
     */
    public function setFreeFormProduct($freeFormProduct)
    {
        $this->freeFormProduct = $freeFormProduct;

        return $this;
    }

    /**
     * @param float $quantity
     *
     * @return $this
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    #[\Override]
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param ProductUnit|null $productUnit
     *
     * @return $this
     */
    public function setProductUnit(ProductUnit $productUnit = null)
    {
        $this->productUnit = $productUnit;

        return $this;
    }

    #[\Override]
    public function getProductUnit()
    {
        return $this->productUnit;
    }

    /**
     * @param string $productUnitCode
     *
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

    /**
     * @param Price|null $price
     *
     * @return $this
     */
    public function setPrice(Price $price = null)
    {
        $this->price = $price;

        $this->updatePrice();

        return $this;
    }

    #[\Override]
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param int $priceType
     *
     * @return $this
     */
    public function setPriceType($priceType)
    {
        $this->priceType = $priceType;

        return $this;
    }

    #[\Override]
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
     *
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
     *
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
        if ($this->value !== null) {
            try {
                return BigDecimal::of($this->value)->toFloat();
            } catch (MathException $e) {
            }
        }

        return $this->value;
    }

    /**
     * @param float $value
     *
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
     *
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
     * @return bool
     */
    public function isPriceFixed()
    {
        return $this->priceFixed;
    }

    /**
     * @param bool $isPriceFixed
     *
     * @return $this
     */
    public function setPriceFixed($isPriceFixed)
    {
        $this->priceFixed = $isPriceFixed;

        return $this;
    }

    public function getShippingMethod(): ?string
    {
        return $this->shippingMethod;
    }

    public function setShippingMethod(?string $shippingMethod): CheckoutLineItem
    {
        $this->shippingMethod = $shippingMethod;

        return $this;
    }

    public function getShippingMethodType(): ?string
    {
        return $this->shippingMethodType;
    }

    public function setShippingMethodType(?string $shippingMethodType): CheckoutLineItem
    {
        $this->shippingMethodType = $shippingMethodType;

        return $this;
    }

    public function getShippingEstimateAmount(): ?float
    {
        return $this->shippingEstimateAmount;
    }

    public function setShippingEstimateAmount(?float $shippingEstimateAmount): CheckoutLineItem
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

    public function updatePrice()
    {
        $this->value = $this->price ? $this->price->getValue() : null;
        $this->currency = $this->price ? $this->price->getCurrency() : null;
    }

    protected function updateItemInformation()
    {
        if ($this->getProduct()) {
            $this->productSku = $this->getProduct()->getSku();
        }

        if ($this->getProductUnit()) {
            $this->productUnitCode = $this->getProductUnit()->getCode();
        }
    }

    public function hasShippingMethodData(): bool
    {
        return (bool)$this->getShippingMethod() && (bool)$this->getShippingMethodType();
    }

    #[\Override]
    public function getLineItemsHolder(): ?ProductLineItemsHolderInterface
    {
        return $this->checkout;
    }

    /**
     * @return Collection<CheckoutProductKitItemLineItem>
     */
    #[\Override]
    public function getKitItemLineItems()
    {
        return $this->kitItemLineItems;
    }

    public function addKitItemLineItem(CheckoutProductKitItemLineItem $productKitItemLineItem): self
    {
        if (!$this->kitItemLineItems->contains($productKitItemLineItem)) {
            $productKitItemLineItem->setLineItem($this);
            $this->kitItemLineItems->add($productKitItemLineItem);
        }

        return $this;
    }

    public function removeKitItemLineItem(CheckoutProductKitItemLineItem $productKitItemLineItem): self
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
