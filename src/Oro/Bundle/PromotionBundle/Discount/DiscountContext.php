<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;

class DiscountContext implements SubtotalAwareInterface, LineItemsAwareInterface
{
    /**
     * @var DiscountLineItem[]
     */
    protected $lineItems;

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
    protected $shippingCost;

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
    public function addShippingDiscount(DiscountInterface $discount)
    {
        $this->shippingDiscounts[] = $discount;
    }

    /**
     * @param DiscountInterface $discount
     */
    public function addSubtotalDiscount(DiscountInterface $discount)
    {
        $this->subtotalDiscounts[] = $discount;
    }

    /**
     * @return DiscountLineItem[]
     */
    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    /**
     * @param DiscountLineItem[] $lineItems
     * @return DiscountContext
     */
    public function setLineItems($lineItems)
    {
        $this->lineItems = $lineItems;

        return $this;
    }

    /**
     * @return array|DiscountInterface[]
     */
    public function getShippingDiscounts(): array
    {
        return $this->shippingDiscounts;
    }

    /**
     * @return array|DiscountInterface[]
     */
    public function getSubtotalDiscounts(): array
    {
        return $this->subtotalDiscounts;
    }

    /**
     * @return float
     */
    public function getShippingCost(): float
    {
        return $this->shippingCost;
    }

    /**
     * @param float $shippingCost
     */
    public function setShippingCost($shippingCost)
    {
        $this->shippingCost = $shippingCost;
    }

    /**
     * @param DiscountInformation $discountInformation
     */
    public function addSubtotalDiscountInformation(DiscountInformation $discountInformation)
    {
        $this->subtotalDiscountsInformation[] = $discountInformation;
    }

    /**
     * @return array|DiscountInformation[]
     */
    public function getSubtotalDiscountsInformation(): array
    {
        return $this->subtotalDiscountsInformation;
    }

    /**
     * @param DiscountInformation $discountInformation
     */
    public function addShippingDiscountInformation(DiscountInformation $discountInformation)
    {
        $this->shippingDiscountsInformation[] = $discountInformation;
    }

    /**
     * @return array|DiscountInformation[]
     */
    public function getShippingDiscountsInformation(): array
    {
        return $this->shippingDiscountsInformation;
    }
}
