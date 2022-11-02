<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\QuantityAwareInterface;

/**
 * Interface for line item which supports adding discount information.
 */
interface DiscountLineItemInterface extends
    QuantityAwareInterface,
    PriceAwareInterface,
    PriceTypeAwareInterface,
    SubtotalAwareInterface
{
    /**
     * @return null|Product
     */
    public function getProduct();

    /**
     * @param Product|null $product
     * @return $this
     */
    public function setProduct(Product $product = null);

    public function getProductSku(): string;

    /**
     * @param string $productSku
     * @return $this
     */
    public function setProductSku($productSku);

    /**
     * @return ProductUnit
     */
    public function getProductUnit();

    /**
     * @param ProductUnit|null $productUnit
     * @return $this
     */
    public function setProductUnit(ProductUnit $productUnit = null);

    public function getProductUnitCode(): string;

    /**
     * @param string $productUnitCode
     * @return $this
     */
    public function setProductUnitCode($productUnitCode);

    /**
     * @param float $quantity
     * @return $this
     */
    public function setQuantity($quantity);

    /**
     * @param float $subtotal
     * @return $this
     */
    public function setSubtotal($subtotal);

    public function getSubtotalAfterDiscounts(): float;

    /**
     * @param float $subtotal
     * @return $this
     */
    public function setSubtotalAfterDiscounts(float $subtotal): self;

    /**
     * @param DiscountInterface $discount
     * @return $this
     */
    public function addDiscount(DiscountInterface $discount);

    /**
     * @return array|DiscountInterface[]
     */
    public function getDiscounts(): array;

    /**
     * @param int $priceType
     * @return $this
     */
    public function setPriceType($priceType);

    /**
     * @param DiscountInformation $discountInformation
     * @return $this
     */
    public function addDiscountInformation(DiscountInformation $discountInformation);

    /**
     * @return array|DiscountInformation[]
     */
    public function getDiscountsInformation(): array;

    public function getDiscountTotal(): float;

    /**
     * @return object
     */
    public function getSourceLineItem();

    /**
     * @param object $sourceLineItem
     * @return $this
     */
    public function setSourceLineItem($sourceLineItem);
}
