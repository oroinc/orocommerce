<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;

class DiscountContext implements SubtotalAwareInterface, LineItemsAwareInterface
{
    /** @var Promotion[] */
    protected $promotions = [];

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
     * @return $this
     */
    public function setSubtotal($subtotal)
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    /**
     * @param DiscountInterface $discount
     * @return $this
     */
    public function addShippingDiscount(DiscountInterface $discount)
    {
        $this->shippingDiscounts[] = $discount;

        return $this;
    }

    /**
     * @param DiscountInterface $discount
     * @return $this
     */
    public function addSubtotalDiscount(DiscountInterface $discount)
    {
        $this->subtotalDiscounts[] = $discount;

        return $this;
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
     * @return $this
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
     * @return $this
     */
    public function setShippingCost($shippingCost)
    {
        $this->shippingCost = $shippingCost;

        return $this;
    }

    /**
     * @param DiscountInformation $discountInformation
     * @return $this
     */
    public function addSubtotalDiscountInformation(DiscountInformation $discountInformation)
    {
        $this->subtotalDiscountsInformation[] = $discountInformation;

        return $this;
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
     * @return $this
     */
    public function addShippingDiscountInformation(DiscountInformation $discountInformation)
    {
        $this->shippingDiscountsInformation[] = $discountInformation;

        return $this;
    }

    /**
     * @return array|DiscountInformation[]
     */
    public function getShippingDiscountsInformation(): array
    {
        return $this->shippingDiscountsInformation;
    }

    /**
     * @return Promotion[]
     */
    public function getPromotions(): array
    {
        return $this->promotions;
    }

    /**
     * @param Promotion[] $promotions
     */
    public function setPromotions(array $promotions)
    {
        $this->promotions = $promotions;
    }
}
