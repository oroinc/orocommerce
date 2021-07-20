<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * Implements DiscountLineItemInterface to support adding discount information on line item's level.
 */
class DiscountLineItem implements DiscountLineItemInterface
{
    /**
     * @var Product|null
     */
    protected $product;

    /**
     * @var Price
     */
    protected $price;

    /**
     * @var int
     */
    protected $priceType = PriceTypeAwareInterface::PRICE_TYPE_UNIT;

    /**
     * @var string
     */
    protected $productSku;

    /**
     * @var float
     */
    protected $quantity = 1.0;

    /**
     * @var ProductUnit
     */
    protected $productUnit;

    /**
     * @var string
     */
    protected $productUnitCode;

    /**
     * @var float
     */
    protected $subtotal = 0.0;

    protected ?float $subtotalAfterDiscounts = null;

    /**
     * @var array|DiscountInterface[]
     */
    protected $discounts = [];

    /**
     * @var array|DiscountInformation[]
     */
    protected $discountsInformation = [];

    /**
     * @var object
     */
    protected $sourceLineItem;

    /**
     * {@inheritdoc}
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param Price $price
     *
     * @return $this
     */
    public function setPrice(Price $price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return null|Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product|null $product
     * @return $this
     */
    public function setProduct(Product $product = null)
    {
        $this->product = $product;

        return $this;
    }

    public function getProductSku(): string
    {
        if ($this->getProduct()) {
            return $this->product->getSku();
        }

        return $this->productSku;
    }

    /**
     * @param string $productSku
     * @return $this
     */
    public function setProductSku($productSku)
    {
        $this->productSku = $productSku;

        return $this;
    }

    /**
     * @return ProductUnit
     */
    public function getProductUnit()
    {
        return $this->productUnit;
    }

    /**
     * @param ProductUnit|null $productUnit
     * @return $this
     */
    public function setProductUnit(ProductUnit $productUnit = null)
    {
        $this->productUnit = $productUnit;

        return $this;
    }

    public function getProductUnitCode(): string
    {
        if ($this->productUnit) {
            return $this->getProductUnit()->getCode();
        }

        return $this->productUnitCode;
    }

    /**
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
    public function getQuantity(): float
    {
        return $this->quantity;
    }

    /**
     * @param float $quantity
     * @return $this
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getSubtotal(): float
    {
        return $this->subtotal;
    }

    /**
     * @param float $subtotal
     * @return $this
     */
    public function setSubtotal($subtotal)
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubtotalAfterDiscounts(): float
    {
        return $this->subtotalAfterDiscounts ?? $this->getSubtotal();
    }

    /**
     * {@inheritdoc}
     */
    public function setSubtotalAfterDiscounts(float $subtotal): self
    {
        $this->subtotalAfterDiscounts = $subtotal;

        return $this;
    }

    /**
     * @param DiscountInterface $discount
     * @return $this
     */
    public function addDiscount(DiscountInterface $discount)
    {
        $this->discounts[] = $discount;

        return $this;
    }

    /**
     * @return array|DiscountInterface[]
     */
    public function getDiscounts(): array
    {
        return $this->discounts;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriceType(): int
    {
        return $this->priceType;
    }

    /**
     * @param int $priceType
     * @return $this
     */
    public function setPriceType($priceType)
    {
        $this->priceType = $priceType;

        return $this;
    }

    /**
     * @param DiscountInformation $discountInformation
     * @return $this
     */
    public function addDiscountInformation(DiscountInformation $discountInformation)
    {
        $this->discountsInformation[] = $discountInformation;

        return $this;
    }

    /**
     * @return array|DiscountInformation[]
     */
    public function getDiscountsInformation(): array
    {
        return $this->discountsInformation;
    }

    public function getDiscountTotal(): float
    {
        $value = 0.0;
        foreach ($this->discountsInformation as $discountInformation) {
            $value += $discountInformation->getDiscountAmount();
        }

        return $value;
    }

    /**
     * @return object
     */
    public function getSourceLineItem()
    {
        return $this->sourceLineItem;
    }

    /**
     * @param object $sourceLineItem
     * @return $this
     */
    public function setSourceLineItem($sourceLineItem)
    {
        $this->sourceLineItem = $sourceLineItem;

        return $this;
    }

    /**
     * Clones (creates new instances) certain fields which can be modified in cloned context
     * to avoid modification of original one
     * Other properties remains the same (ref links for objects)
     */
    public function __clone()
    {
        $this->price = \is_object($this->price) ? clone $this->price : null;
        $this->productUnit = \is_object($this->productUnit) ? clone $this->productUnit : null;
        $this->discounts = \array_map(
            function ($o) {
                return clone $o;
            },
            $this->discounts
        );
        $this->discountsInformation = \array_map(
            function ($o) {
                return clone $o;
            },
            $this->discountsInformation
        );
        $this->sourceLineItem = \is_object($this->sourceLineItem) ? clone $this->sourceLineItem : null;
    }
}
