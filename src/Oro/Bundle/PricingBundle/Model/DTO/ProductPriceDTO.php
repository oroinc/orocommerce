<?php

namespace Oro\Bundle\PricingBundle\Model\DTO;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Combines information about price, its quantity and price
 */
class ProductPriceDTO implements \JsonSerializable, ProductPriceInterface
{
    public const PRICE = 'price';
    public const CURRENCY = 'currency';
    public const QUANTITY = 'quantity';
    public const UNIT = 'unit';
    public const PRODUCT = 'product_id';

    /**
     * @var float
     */
    protected $quantity;

    /**
     * @var Price
     */
    protected $price;

    /**
     * @var MeasureUnitInterface
     */
    protected $unit;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @param Product $product
     * @param Price $price
     * @param float $quantity
     * @param MeasureUnitInterface $unit
     */
    public function __construct(Product $product, Price $price, $quantity, MeasureUnitInterface $unit)
    {
        $this->setProduct($product);
        $this->setPrice($price);
        $this->setQuantity($quantity);
        $this->setUnit($unit);
    }

    #[\Override]
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param float $quantity
     * @return $this
     */
    public function setQuantity($quantity)
    {
        $this->quantity = (float)$quantity;

        return $this;
    }

    #[\Override]
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param Price $price
     * @return $this
     */
    public function setPrice(Price $price)
    {
        $this->price = $price;

        return $this;
    }

    #[\Override]
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param MeasureUnitInterface $unit
     * @return $this
     */
    public function setUnit(MeasureUnitInterface $unit)
    {
        $this->unit = $unit;

        return $this;
    }

    #[\Override]
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     * @return $this
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;

        return $this;
    }

    public function toArray(): array
    {
        return [
            self::PRICE => $this->price->getValue(),
            self::CURRENCY => $this->price->getCurrency(),
            self::QUANTITY => $this->getQuantity(),
            self::UNIT => $this->unit->getCode(),
            self::PRODUCT => $this->product->getId()
        ];
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
