<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use Oro\Bundle\ProductBundle\Model\QuantityAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;

class DiscountLineItem implements
    ProductUnitHolderInterface,
    ProductHolderInterface,
    QuantityAwareInterface,
    PriceAwareInterface
{
    /**
     * @var array|DiscountInformation[]
     */
    protected $discounts = [];

    /**
     * @var DiscountInformation
     */
    protected $appliedDiscount;

    /**
     * @var Promotion[]
     */
    protected $promotions = [];

    public function getPrice()
    {
        // TODO: Implement getPrice() method.
    }

    public function getProduct()
    {
        // TODO: Implement getProduct() method.
    }

    public function getProductSku()
    {
        // TODO: Implement getProductSku() method.
    }

    public function getEntityIdentifier()
    {
        // TODO: Implement getEntityIdentifier() method.
    }

    public function getProductHolder()
    {
        // TODO: Implement getProductHolder() method.
    }

    public function getProductUnit()
    {
        // TODO: Implement getProductUnit() method.
    }

    public function getProductUnitCode()
    {
        // TODO: Implement getProductUnitCode() method.
    }

    public function getQuantity()
    {
        // TODO: Implement getQuantity() method.
    }

    public function addDiscount53Information(DiscountInformation $discountInformation)
    {
        $this->discounts[] = $discountInformation;
    }
}
