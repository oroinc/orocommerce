<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\CurrencyBundle\Entity\SettablePriceAwareInterface;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\QuantityAwareInterface;

class DiscountLineItem implements
    QuantityAwareInterface,
    PriceAwareInterface,
    PriceTypeAwareInterface,
    SettablePriceAwareInterface,
    SubtotalAwareInterface
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

    /**
     * @var array|DiscountInterface[]
     */
    protected $discounts = [];

    /**
     * @var array|DiscountInformation[]
     */
    protected $discountsInformation = [];

    /**
     * {@inheritdoc}
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * {@inheritdoc}
     */
    public function setPrice(Price $price)
    {
        $this->price = $price;
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
     */
    public function setProduct(Product $product = null)
    {
        $this->product = $product;
        if ($product) {
            $this->setProductSku($product->getSku());
        }
    }

    /**
     * @return string
     */
    public function getProductSku(): string
    {
        if ($this->getProduct()) {
            return $this->product->getSku();
        }

        return $this->productSku;
    }

    /**
     * @param string $productSku
     */
    public function setProductSku($productSku)
    {
        $this->productSku = $productSku;
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
     */
    public function setProductUnit(ProductUnit $productUnit = null)
    {
        $this->productUnit = $productUnit;
        if ($productUnit) {
            $this->setProductUnitCode($productUnit->getCode());
        }
    }

    /**
     * @return string
     */
    public function getProductUnitCode(): string
    {
        if ($this->productUnit) {
            return $this->getProductUnit()->getCode();
        }

        return $this->productUnitCode;
    }

    /**
     * @param string $productUnitCode
     */
    public function setProductUnitCode($productUnitCode)
    {
        $this->productUnitCode = $productUnitCode;
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
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @return float
     */
    public function getSubtotal(): float
    {
        return $this->subtotal;
    }

    /**
     * @param float $subtotal
     */
    public function setSubtotal($subtotal)
    {
        $this->subtotal = $subtotal;
    }

    /**
     * @param DiscountInterface $discount
     */
    public function addDiscount(DiscountInterface $discount)
    {
        $this->discounts[] = $discount;
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
     */
    public function setPriceType($priceType)
    {
        $this->priceType = $priceType;
    }

    /**
     * @param DiscountInformation $discountInformation
     */
    public function addDiscountInformation(DiscountInformation $discountInformation)
    {
        $this->discountsInformation[] = $discountInformation;
    }

    /**
     * @return array|DiscountInformation[]
     */
    public function getDiscountsInformation(): array
    {
        return $this->discountsInformation;
    }
}
