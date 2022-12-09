<?php

namespace Oro\Bundle\CheckoutBundle\Entity;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Represents checkout item.
 *
 * @ORM\Table(name="oro_checkout_line_item")
 * @ORM\Entity(repositoryClass="Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutLineItemRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      mode="hidden"
 * )
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class CheckoutLineItem implements
    PriceAwareInterface,
    PriceTypeAwareInterface,
    ProductLineItemInterface,
    ShippingAwareInterface
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Checkout
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CheckoutBundle\Entity\Checkout", inversedBy="lineItems")
     * @ORM\JoinColumn(name="checkout_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $checkout;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $product;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="parent_product_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $parentProduct;

    /**
     * @var string
     *
     * @ORM\Column(name="product_sku", type="string", length=255, nullable=true)
     */
    protected $productSku;

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
     * Hold flag to determine if price can be changed
     *
     * @var bool
     *
     * @ORM\Column(name="is_price_fixed", type="boolean")
     */
    protected $priceFixed = false;

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
     * @return string
     */
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
     * @param Product $product
     *
     * @return $this
     */
    public function setProduct(Product $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * {@inheritDoc}
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

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param ProductUnit $productUnit
     *
     * @return $this
     */
    public function setProductUnit(ProductUnit $productUnit = null)
    {
        $this->productUnit = $productUnit;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
    public function getProductUnitCode()
    {
        return $this->productUnitCode;
    }

    /**
     * @param Price $price
     *
     * @return $this
     */
    public function setPrice(Price $price = null)
    {
        $this->price = $price;

        $this->updatePrice();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
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

    /**
     * @return null|string
     */
    public function getShippingMethod(): ?string
    {
        return $this->shippingMethod;
    }

    /**
     * @param null|string $shippingMethod
     * @return CheckoutLineItem
     */
    public function setShippingMethod(?string $shippingMethod): CheckoutLineItem
    {
        $this->shippingMethod = $shippingMethod;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getShippingMethodType(): ?string
    {
        return $this->shippingMethodType;
    }

    /**
     * @param null|string $shippingMethodType
     * @return CheckoutLineItem
     */
    public function setShippingMethodType(?string $shippingMethodType): CheckoutLineItem
    {
        $this->shippingMethodType = $shippingMethodType;

        return $this;
    }

    /**
     * @return float
     */
    public function getShippingEstimateAmount(): ?float
    {
        return $this->shippingEstimateAmount;
    }

    /**
     * @param null|float $shippingEstimateAmount
     * @return CheckoutLineItem
     */
    public function setShippingEstimateAmount(?float $shippingEstimateAmount): CheckoutLineItem
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
}
