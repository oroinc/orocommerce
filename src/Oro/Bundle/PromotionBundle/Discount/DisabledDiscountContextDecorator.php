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

    #[\Override]
    public function getSubtotal(): float
    {
        return $this->context->getSubtotal();
    }

    /**
     * @param float $subtotal
     * @return $this
     */
    #[\Override]
    public function setSubtotal($subtotal)
    {
        $this->context->setSubtotal($subtotal);

        return $this;
    }

    /**
     * @param DiscountInterface $discount
     * @return $this
     */
    #[\Override]
    public function addShippingDiscount(DiscountInterface $discount)
    {
        $this->context->addShippingDiscount(new DisabledDiscountDecorator($discount));

        return $this;
    }

    /**
     * @param DiscountInterface $discount
     * @return $this
     */
    #[\Override]
    public function addSubtotalDiscount(DiscountInterface $discount)
    {
        $this->context->addSubtotalDiscount(new DisabledDiscountDecorator($discount));

        return $this;
    }

    /**
     * @return DiscountLineItem[]
     */
    #[\Override]
    public function getLineItems(): array
    {
        $lineItems = $this->context->getLineItems();
        foreach ($lineItems as $key => $lineItem) {
            if (!$lineItem instanceof DisabledDiscountLineItemDecorator) {
                $lineItems[$key] = new DisabledDiscountLineItemDecorator($lineItem);
            }
        }

        return $lineItems;
    }

    /**
     * @param DiscountLineItem[] $lineItems
     * @return $this
     */
    #[\Override]
    public function setLineItems($lineItems)
    {
        $this->context->setLineItems($lineItems);

        return $this;
    }

    /**
     * @param DiscountLineItem $lineItem
     * @return $this
     */
    #[\Override]
    public function addLineItem(DiscountLineItem $lineItem)
    {
        $this->context->addLineItem($lineItem);

        return $this;
    }

    /**
     * @return array|DiscountInterface[]
     */
    #[\Override]
    public function getShippingDiscounts(): array
    {
        return $this->context->getShippingDiscounts();
    }

    /**
     * @return array|DiscountInterface[]
     */
    #[\Override]
    public function getSubtotalDiscounts(): array
    {
        return $this->context->getSubtotalDiscounts();
    }

    /**
     * @return array|DiscountInterface[]
     */
    #[\Override]
    public function getLineItemDiscounts()
    {
        return $this->context->getLineItemDiscounts();
    }

    #[\Override]
    public function getShippingCost(): float
    {
        return $this->context->getShippingCost();
    }

    /**
     * @param float $shippingCost
     * @return $this
     */
    #[\Override]
    public function setShippingCost($shippingCost)
    {
        $this->context->setShippingCost($shippingCost);

        return $this;
    }

    /**
     * @param DiscountInformation $discountInformation
     * @return $this
     */
    #[\Override]
    public function addSubtotalDiscountInformation(DiscountInformation $discountInformation)
    {
        $this->context->addSubtotalDiscountInformation($discountInformation);

        return $this;
    }

    /**
     * @return array|DiscountInformation[]
     */
    #[\Override]
    public function getSubtotalDiscountsInformation(): array
    {
        return $this->context->getSubtotalDiscountsInformation();
    }

    /**
     * @param DiscountInformation $discountInformation
     * @return $this
     */
    #[\Override]
    public function addShippingDiscountInformation(DiscountInformation $discountInformation)
    {
        $this->context->addShippingDiscountInformation($discountInformation);

        return $this;
    }

    /**
     * @return array|DiscountInformation[]
     */
    #[\Override]
    public function getShippingDiscountsInformation(): array
    {
        return $this->context->getShippingDiscountsInformation();
    }

    #[\Override]
    public function getShippingDiscountTotal(): float
    {
        return $this->context->getShippingDiscountTotal();
    }

    #[\Override]
    public function getSubtotalDiscountTotal(): float
    {
        return $this->context->getSubtotalDiscountTotal();
    }

    #[\Override]
    public function getTotalLineItemsDiscount(): float
    {
        return $this->context->getTotalLineItemsDiscount();
    }

    /**
     * @param object $lineItem
     * @return float
     */
    #[\Override]
    public function getDiscountByLineItem($lineItem): float
    {
        return $this->context->getDiscountByLineItem($lineItem);
    }

    #[\Override]
    public function getTotalDiscountAmount(): float
    {
        return $this->context->getTotalDiscountAmount();
    }
}
