<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;

/**
 * Interface for context to support adding discount's information.
 */
interface DiscountContextInterface extends SubtotalAwareInterface, LineItemsAwareInterface, ShippingAwareInterface
{
    /**
     * @param float $subtotal
     * @return $this
     */
    public function setSubtotal($subtotal);

    /**
     * @param DiscountInterface $discount
     * @return $this
     */
    public function addShippingDiscount(DiscountInterface $discount);

    /**
     * @param DiscountInterface $discount
     * @return $this
     */
    public function addSubtotalDiscount(DiscountInterface $discount);

    /**
     * @param DiscountLineItem[] $lineItems
     * @return $this
     */
    public function setLineItems($lineItems);

    /**
     * @param DiscountLineItem $lineItem
     * @return $this
     */
    public function addLineItem(DiscountLineItem $lineItem);

    /**
     * @return array|DiscountInterface[]
     */
    public function getShippingDiscounts(): array;

    /**
     * @return array|DiscountInterface[]
     */
    public function getSubtotalDiscounts(): array;

    /**
     * @return array|DiscountInterface[]
     */
    public function getLineItemDiscounts();

    public function getShippingCost(): float;

    /**
     * @param float $shippingCost
     * @return $this
     */
    public function setShippingCost($shippingCost);

    /**
     * @param DiscountInformation $discountInformation
     * @return $this
     */
    public function addSubtotalDiscountInformation(DiscountInformation $discountInformation);

    /**
     * @return array|DiscountInformation[]
     */
    public function getSubtotalDiscountsInformation(): array;

    /**
     * @param DiscountInformation $discountInformation
     * @return $this
     */
    public function addShippingDiscountInformation(DiscountInformation $discountInformation);

    /**
     * @return array|DiscountInformation[]
     */
    public function getShippingDiscountsInformation(): array;

    public function getShippingDiscountTotal(): float;

    public function getSubtotalDiscountTotal(): float;

    public function getTotalLineItemsDiscount(): float;

    /**
     * @param object $lineItem
     * @return float
     */
    public function getDiscountByLineItem($lineItem): float;

    public function getTotalDiscountAmount(): float;
}
