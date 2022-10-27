<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * Decorates DiscountLineItem to add disabled discounts (i.e. decorated with DisabledDiscountDecorator).
 */
class DisabledDiscountLineItemDecorator implements DiscountLineItemInterface
{
    /**
     * @var DiscountLineItem
     */
    private $lineItem;

    public function __construct(DiscountLineItem $lineItem)
    {
        $this->lineItem = $lineItem;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrice()
    {
        return $this->lineItem->getPrice();
    }

    /**
     * @param Price $price
     *
     * @return $this
     */
    public function setPrice(Price $price)
    {
        $this->lineItem->setPrice($price);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getProduct()
    {
        return $this->lineItem->getProduct();
    }

    /**
     * {@inheritdoc}
     */
    public function setProduct(Product $product = null)
    {
        $this->lineItem->setProduct($product);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductSku(): string
    {
        return $this->lineItem->getProductSku();
    }

    /**
     * {@inheritdoc}
     */
    public function setProductSku($productSku)
    {
        $this->lineItem->setProductSku($productSku);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductUnit()
    {
        return $this->lineItem->getProductUnit();
    }

    /**
     * {@inheritdoc}
     */
    public function setProductUnit(ProductUnit $productUnit = null)
    {
        $this->lineItem->setProductUnit($productUnit);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getProductUnitCode(): string
    {
        return $this->lineItem->getProductUnitCode();
    }

    /**
     * {@inheritdoc}
     */
    public function setProductUnitCode($productUnitCode)
    {
        $this->lineItem->setProductUnitCode($productUnitCode);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuantity(): float
    {
        return $this->lineItem->getQuantity();
    }

    /**
     * {@inheritdoc}
     */
    public function setQuantity($quantity)
    {
        $this->lineItem->setQuantity($quantity);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubtotal(): float
    {
        return $this->lineItem->getSubtotal();
    }

    /**
     * {@inheritdoc}
     */
    public function setSubtotal($subtotal)
    {
        $this->lineItem->setSubtotal($subtotal);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubtotalAfterDiscounts(): float
    {
        return $this->lineItem->getSubtotalAfterDiscounts();
    }

    /**
     * {@inheritdoc}
     */
    public function setSubtotalAfterDiscounts(float $subtotal): self
    {
        $this->lineItem->setSubtotalAfterDiscounts($subtotal);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addDiscount(DiscountInterface $discount)
    {
        $this->lineItem->addDiscount(new DisabledDiscountDecorator($discount));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDiscounts(): array
    {
        return $this->lineItem->getDiscounts();
    }

    /**
     * {@inheritdoc}
     */
    public function getPriceType(): int
    {
        return $this->lineItem->getPriceType();
    }

    /**
     * {@inheritdoc}
     */
    public function setPriceType($priceType)
    {
        $this->lineItem->setPriceType($priceType);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addDiscountInformation(DiscountInformation $discountInformation)
    {
        $this->lineItem->addDiscountInformation($discountInformation);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDiscountsInformation(): array
    {
        return $this->lineItem->getDiscountsInformation();
    }

    /**
     * {@inheritdoc}
     */
    public function getDiscountTotal(): float
    {
        return $this->lineItem->getDiscountTotal();
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceLineItem()
    {
        return $this->lineItem->getSourceLineItem();
    }

    /**
     * {@inheritdoc}
     */
    public function setSourceLineItem($sourceLineItem)
    {
        $this->lineItem->setSourceLineItem($sourceLineItem);

        return $this;
    }
}
