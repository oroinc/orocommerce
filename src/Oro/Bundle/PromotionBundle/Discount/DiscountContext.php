<?php

namespace Oro\Bundle\PromotionBundle\Discount;

/**
 * Implements DiscountContextInterface providing ability for discounts to add information into it.
 */
class DiscountContext implements DiscountContextInterface
{
    /**
     * @var DiscountLineItem[]
     */
    protected $lineItems = [];

    /**
     * @var array|DiscountInterface[]
     */
    protected $subtotalDiscounts = [];

    /**
     * @var array|DiscountInformation[]
     */
    protected $subtotalDiscountsInformation = [];

    /**
     * @var array|DiscountInterface[]
     */
    protected $shippingDiscounts = [];

    /**
     * @var array|DiscountInformation[]
     */
    protected $shippingDiscountsInformation = [];

    /**
     * @var float
     */
    protected $subtotal;

    /**
     * @var float
     */
    protected $shippingCost = 0.0;

    /**
     * {@inheritdoc}
     */
    public function getSubtotal(): float
    {
        return $this->subtotal;
    }

    /**
     * {@inheritdoc}
     */
    public function setSubtotal($subtotal)
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addShippingDiscount(DiscountInterface $discount)
    {
        $this->shippingDiscounts[] = $discount;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addSubtotalDiscount(DiscountInterface $discount)
    {
        $this->subtotalDiscounts[] = $discount;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    /**
     * {@inheritdoc}
     */
    public function setLineItems($lineItems)
    {
        $this->lineItems = $lineItems;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addLineItem(DiscountLineItem $lineItem)
    {
        $this->lineItems[] = $lineItem;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingDiscounts(): array
    {
        return $this->shippingDiscounts;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubtotalDiscounts(): array
    {
        return $this->subtotalDiscounts;
    }

    /**
     * {@inheritdoc}
     */
    public function getLineItemDiscounts()
    {
        $discounts = [];
        foreach ($this->lineItems as $lineItem) {
            foreach ($lineItem->getDiscounts() as $discount) {
                $discounts[spl_object_hash($discount)] = $discount;
            }
        }

        return array_values($discounts);
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingCost(): float
    {
        return $this->shippingCost;
    }

    /**
     * {@inheritdoc}
     */
    public function setShippingCost($shippingCost)
    {
        $this->shippingCost = $shippingCost;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addSubtotalDiscountInformation(DiscountInformation $discountInformation)
    {
        $this->subtotalDiscountsInformation[] = $discountInformation;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubtotalDiscountsInformation(): array
    {
        return $this->subtotalDiscountsInformation;
    }

    /**
     * {@inheritdoc}
     */
    public function addShippingDiscountInformation(DiscountInformation $discountInformation)
    {
        $this->shippingDiscountsInformation[] = $discountInformation;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingDiscountsInformation(): array
    {
        return $this->shippingDiscountsInformation;
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingDiscountTotal(): float
    {
        $value = 0.0;
        foreach ($this->shippingDiscountsInformation as $discountInformation) {
            $value += $discountInformation->getDiscountAmount();
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubtotalDiscountTotal(): float
    {
        $value = 0.0;
        foreach ($this->subtotalDiscountsInformation as $discountInformation) {
            $value += $discountInformation->getDiscountAmount();
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalLineItemsDiscount(): float
    {
        $value = 0.0;
        foreach ($this->getLineItems() as $lineItem) {
            $value += $lineItem->getDiscountTotal();
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getDiscountByLineItem($lineItem): float
    {
        $amount = 0.0;
        foreach ($this->getLineItems() as $discountLineItem) {
            if ($discountLineItem->getSourceLineItem() === $lineItem) {
                $amount += $discountLineItem->getDiscountTotal();
            }
        }

        return $amount;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalDiscountAmount(): float
    {
        return $this->getTotalLineItemsDiscount()
            + $this->getSubtotalDiscountTotal()
            + $this->getShippingDiscountTotal();
    }

    /**
     * Employs custom cloning of the line items collection (to avoid
     * unnecessary cloning of products inside of the line items)
     */
    public function __clone()
    {
        $this->lineItems = \array_map(
            function ($item) {
                return clone $item;
            },
            $this->lineItems
        );
    }
}
