<?php

namespace Oro\Bundle\PromotionBundle\Discount;

/**
 * Decorates DiscountContext to add disabled discounts to it (i.e. decorated with DisabledDiscountDecorator).
 * Also decorates DiscountLineItem[] $lineItems with DisabledDiscountLineItemDecorator for the same purpose.
 */
class DisabledDiscountContextDecorator implements DiscountContextInterface
{
    /**
     * @var DiscountContext
     */
    private $context;

    public function __construct(DiscountContextInterface $discountContext)
    {
        $this->context = $discountContext;
    }

    public function getSubtotal(): float
    {
        return $this->context->getSubtotal();
    }

    /**
     * @param float $subtotal
     * @return $this
     */
    public function setSubtotal($subtotal)
    {
        $this->context->setSubtotal($subtotal);

        return $this;
    }

    /**
     * @param DiscountInterface $discount
     * @return $this
     */
    public function addShippingDiscount(DiscountInterface $discount)
    {
        $this->context->addShippingDiscount(new DisabledDiscountDecorator($discount));

        return $this;
    }

    /**
     * @param DiscountInterface $discount
     * @return $this
     */
    public function addSubtotalDiscount(DiscountInterface $discount)
    {
        $this->context->addSubtotalDiscount(new DisabledDiscountDecorator($discount));

        return $this;
    }

    /**
     * @return DiscountLineItem[]
     */
    public function getLineItems(): array
    {
        $lineItems = [];
        foreach ($this->context->getLineItems() as $lineItem) {
            $lineItems[] = new DisabledDiscountLineItemDecorator($lineItem);
        }

        return $lineItems;
    }

    /**
     * @param DiscountLineItem[] $lineItems
     * @return $this
     */
    public function setLineItems($lineItems)
    {
        $this->context->setLineItems($lineItems);

        return $this;
    }

    /**
     * @param DiscountLineItem $lineItem
     * @return $this
     */
    public function addLineItem(DiscountLineItem $lineItem)
    {
        $this->context->addLineItem($lineItem);

        return $this;
    }

    /**
     * @return array|DiscountInterface[]
     */
    public function getShippingDiscounts(): array
    {
        return $this->context->getShippingDiscounts();
    }

    /**
     * @return array|DiscountInterface[]
     */
    public function getSubtotalDiscounts(): array
    {
        return $this->context->getSubtotalDiscounts();
    }

    /**
     * @return array|DiscountInterface[]
     */
    public function getLineItemDiscounts()
    {
        return $this->context->getLineItemDiscounts();
    }

    public function getShippingCost(): float
    {
        return $this->context->getShippingCost();
    }

    /**
     * @param float $shippingCost
     * @return $this
     */
    public function setShippingCost($shippingCost)
    {
        $this->context->setShippingCost($shippingCost);

        return $this;
    }

    /**
     * @param DiscountInformation $discountInformation
     * @return $this
     */
    public function addSubtotalDiscountInformation(DiscountInformation $discountInformation)
    {
        $this->context->addSubtotalDiscountInformation($discountInformation);

        return $this;
    }

    /**
     * @return array|DiscountInformation[]
     */
    public function getSubtotalDiscountsInformation(): array
    {
        return $this->context->getSubtotalDiscountsInformation();
    }

    /**
     * @param DiscountInformation $discountInformation
     * @return $this
     */
    public function addShippingDiscountInformation(DiscountInformation $discountInformation)
    {
        $this->context->addShippingDiscountInformation($discountInformation);

        return $this;
    }

    /**
     * @return array|DiscountInformation[]
     */
    public function getShippingDiscountsInformation(): array
    {
        return $this->context->getShippingDiscountsInformation();
    }

    public function getShippingDiscountTotal(): float
    {
        return $this->context->getShippingDiscountTotal();
    }

    public function getSubtotalDiscountTotal(): float
    {
        return $this->context->getSubtotalDiscountTotal();
    }

    public function getTotalLineItemsDiscount(): float
    {
        return $this->context->getTotalLineItemsDiscount();
    }

    /**
     * @param object $lineItem
     * @return float
     */
    public function getDiscountByLineItem($lineItem): float
    {
        return $this->context->getDiscountByLineItem($lineItem);
    }

    public function getTotalDiscountAmount(): float
    {
        return $this->context->getTotalDiscountAmount();
    }
}
