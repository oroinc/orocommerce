<?php

namespace Oro\Bundle\CheckoutBundle\Entity;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\ArithmeticException;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\SettablePriceAwareInterface;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Checkout\LineItem\CheckoutLineItemInterface;

/**
 * @ORM\Table(name="oro_checkout_line_item")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CheckoutLineItem implements SettablePriceAwareInterface, PriceTypeAwareInterface, CheckoutLineItemInterface
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
     * @var bool
     */
    protected $requirePriceRecalculation = false;

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
     * Set product
     *
     * @param Product $product
     *
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
    public function setParentProduct(Product $parentProduct)
    {
        $this->parentProduct = $parentProduct;

        return $this;
    }

    /**
     * Set productSku
     *
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
     * Set quantity
     *
     * @param float $quantity
     *
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
     * {@inheritDoc}
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set productUnit
     *
     * @param ProductUnit $productUnit
     *
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
     * {@inheritDoc}
     */
    public function getProductUnit()
    {
        return $this->productUnit;
    }

    /**
     * Set productUnitCode
     *
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
     * {@inheritDoc}
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
     * Set priceType
     *
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
            } catch (ArithmeticException $e) {
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
}
