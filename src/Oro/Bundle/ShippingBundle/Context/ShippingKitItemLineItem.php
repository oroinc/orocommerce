<?php

namespace Oro\Bundle\ShippingBundle\Context;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use Oro\Bundle\ProductBundle\Model\QuantityAwareInterface;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecorator;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Container of values that represents Shipping Kit Item Line Item.
 */
class ShippingKitItemLineItem extends ParameterBag implements
    ProductUnitHolderInterface,
    ProductShippingOptionsInterface,
    ProductHolderInterface,
    QuantityAwareInterface,
    PriceAwareInterface,
    ProductKitItemAwareInterface
{
    public const FIELD_ENTITY_IDENTIFIER = 'entity_id';
    public const FIELD_PRODUCT = 'product';
    public const FIELD_PRODUCT_SKU = 'product_sku';
    public const FIELD_PRODUCT_HOLDER = 'product_holder';
    public const FIELD_PRODUCT_UNIT = 'product_unit';
    public const FIELD_PRODUCT_UNIT_CODE = 'product_unit_code';
    public const FIELD_QUANTITY = 'quantity';
    public const FIELD_PRICE = 'price';
    public const FIELD_KIT_ITEM = 'kit_item';
    public const FIELD_SORT_ORDER = 'sort_order';
    public const FIELD_WEIGHT = 'weight';
    public const FIELD_DIMENSIONS = 'dimensions';

    public function __construct(ProductHolderInterface $productHolder)
    {
        parent::__construct([
            self::FIELD_PRODUCT_HOLDER => $productHolder,
            self::FIELD_ENTITY_IDENTIFIER => $productHolder->getEntityIdentifier(),
            self::FIELD_SORT_ORDER => 0,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityIdentifier()
    {
        return $this->get(self::FIELD_ENTITY_IDENTIFIER);
    }

    public function getProduct(): Product|VirtualFieldsProductDecorator|null
    {
        return $this->get(self::FIELD_PRODUCT);
    }

    public function getProductSku(): ?string
    {
        return $this->get(self::FIELD_PRODUCT_SKU);
    }

    public function getProductHolder(): ProductHolderInterface
    {
        return $this->get(self::FIELD_PRODUCT_HOLDER);
    }

    public function getProductUnit(): ?ProductUnit
    {
        return $this->get(self::FIELD_PRODUCT_UNIT);
    }

    public function getProductUnitCode(): ?string
    {
        return $this->get(self::FIELD_PRODUCT_UNIT_CODE);
    }

    public function getQuantity(): float
    {
        return (float)$this->get(self::FIELD_QUANTITY);
    }

    public function getPrice(): ?Price
    {
        return $this->get(self::FIELD_PRICE);
    }

    public function getKitItem(): ?ProductKitItem
    {
        return $this->get(self::FIELD_KIT_ITEM);
    }

    public function getSortOrder(): int
    {
        return $this->get(self::FIELD_SORT_ORDER, 0);
    }

    public function setProductUnit(?ProductUnit $productUnit): self
    {
        $this->set(self::FIELD_PRODUCT_UNIT, $productUnit);

        return $this;
    }

    public function setProductUnitCode(string $productUnitCode): self
    {
        $this->set(self::FIELD_PRODUCT_UNIT_CODE, $productUnitCode);

        return $this;
    }

    public function setQuantity(float|int $quantity): self
    {
        $this->set(self::FIELD_QUANTITY, $quantity);

        return $this;
    }

    public function setProduct(Product|VirtualFieldsProductDecorator|null $product): self
    {
        $this->set(self::FIELD_PRODUCT, $product);

        return $this;
    }

    public function setProductSku(?string $sku): self
    {
        $this->set(self::FIELD_PRODUCT_SKU, $sku);

        return $this;
    }

    public function setPrice(?Price $price): self
    {
        $this->set(self::FIELD_PRICE, $price);

        return $this;
    }

    public function setKitItem(?ProductKitItem $kitItem): self
    {
        $this->set(self::FIELD_KIT_ITEM, $kitItem);

        return $this;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->set(self::FIELD_SORT_ORDER, $sortOrder);

        return $this;
    }

    public function getWeight(): ?Weight
    {
        return $this->get(self::FIELD_WEIGHT);
    }

    public function getDimensions(): ?Dimensions
    {
        return $this->get(self::FIELD_DIMENSIONS);
    }

    public function setWeight(?Weight $weight): self
    {
        $this->set(self::FIELD_WEIGHT, $weight);

        return $this;
    }

    public function setDimensions(?Dimensions $dimensions): self
    {
        $this->set(self::FIELD_DIMENSIONS, $dimensions);

        return $this;
    }
}
