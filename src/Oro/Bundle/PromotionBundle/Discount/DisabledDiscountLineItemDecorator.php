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

    #[\Override]
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

    #[\Override]
    public function getProduct()
    {
        return $this->lineItem->getProduct();
    }

    #[\Override]
    public function setProduct(Product $product = null)
    {
        $this->lineItem->setProduct($product);

        return $this;
    }

    #[\Override]
    public function getProductSku(): string
    {
        return $this->lineItem->getProductSku();
    }

    #[\Override]
    public function setProductSku($productSku)
    {
        $this->lineItem->setProductSku($productSku);

        return $this;
    }

    #[\Override]
    public function getProductUnit()
    {
        return $this->lineItem->getProductUnit();
    }

    #[\Override]
    public function setProductUnit(ProductUnit $productUnit = null)
    {
        $this->lineItem->setProductUnit($productUnit);

        return $this;
    }

    #[\Override]
    public function getProductUnitCode(): string
    {
        return $this->lineItem->getProductUnitCode();
    }

    #[\Override]
    public function setProductUnitCode($productUnitCode)
    {
        $this->lineItem->setProductUnitCode($productUnitCode);

        return $this;
    }

    #[\Override]
    public function getQuantity(): float
    {
        return $this->lineItem->getQuantity();
    }

    #[\Override]
    public function setQuantity($quantity)
    {
        $this->lineItem->setQuantity($quantity);

        return $this;
    }

    #[\Override]
    public function getSubtotal(): float
    {
        return $this->lineItem->getSubtotal();
    }

    #[\Override]
    public function setSubtotal($subtotal)
    {
        $this->lineItem->setSubtotal($subtotal);

        return $this;
    }

    #[\Override]
    public function getSubtotalAfterDiscounts(): float
    {
        return $this->lineItem->getSubtotalAfterDiscounts();
    }

    #[\Override]
    public function setSubtotalAfterDiscounts(float $subtotal): self
    {
        $this->lineItem->setSubtotalAfterDiscounts($subtotal);

        return $this;
    }

    #[\Override]
    public function addDiscount(DiscountInterface $discount)
    {
        $this->lineItem->addDiscount(new DisabledDiscountDecorator($discount));

        return $this;
    }

    #[\Override]
    public function getDiscounts(): array
    {
        return $this->lineItem->getDiscounts();
    }

    #[\Override]
    public function getPriceType(): int
    {
        return $this->lineItem->getPriceType();
    }

    #[\Override]
    public function setPriceType($priceType)
    {
        $this->lineItem->setPriceType($priceType);

        return $this;
    }

    #[\Override]
    public function addDiscountInformation(DiscountInformation $discountInformation)
    {
        $this->lineItem->addDiscountInformation($discountInformation);

        return $this;
    }

    #[\Override]
    public function getDiscountsInformation(): array
    {
        return $this->lineItem->getDiscountsInformation();
    }

    #[\Override]
    public function getDiscountTotal(): float
    {
        return $this->lineItem->getDiscountTotal();
    }

    #[\Override]
    public function getSourceLineItem()
    {
        return $this->lineItem->getSourceLineItem();
    }

    #[\Override]
    public function setSourceLineItem($sourceLineItem)
    {
        $this->lineItem->setSourceLineItem($sourceLineItem);

        return $this;
    }
}
