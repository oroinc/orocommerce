<?php

namespace Oro\Bundle\ShippingBundle\Context;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemChecksumAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use Oro\Bundle\ProductBundle\Model\QuantityAwareInterface;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecorator;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Container of values that represents Shipping Line Item.
 */
class ShippingLineItem extends ParameterBag implements
    ProductUnitHolderInterface,
    ProductShippingOptionsInterface,
    ProductHolderInterface,
    QuantityAwareInterface,
    PriceAwareInterface,
    ProductLineItemChecksumAwareInterface,
    ProductKitItemLineItemsAwareInterface
{
    public const FIELD_PRICE = 'price';
    public const FIELD_PRODUCT = 'product';
    public const FIELD_PRODUCT_HOLDER = 'product_holder';
    public const FIELD_PRODUCT_SKU = 'product_sku';
    public const FIELD_ENTITY_IDENTIFIER = 'entity_id';
    public const FIELD_QUANTITY = 'quantity';
    public const FIELD_PRODUCT_UNIT = 'product_unit';
    public const FIELD_PRODUCT_UNIT_CODE = 'product_unit_code';
    public const FIELD_WEIGHT = 'weight';
    public const FIELD_DIMENSIONS = 'dimensions';
    public const FIELD_KIT_ITEM_LINE_ITEMS = 'kit_item_line_items';
    public const FIELD_CHECKSUM = 'checksum';

    public function __construct(
        ProductUnit $productUnit,
        float|int $quantity,
        ProductHolderInterface $productHolder
    ) {
        parent::__construct([
            self::FIELD_PRODUCT_UNIT => $productUnit,
            self::FIELD_PRODUCT_UNIT_CODE => $productUnit->getCode(),
            self::FIELD_QUANTITY => $quantity,
            self::FIELD_PRODUCT_HOLDER => $productHolder,
            self::FIELD_ENTITY_IDENTIFIER => $productHolder->getEntityIdentifier(),
            self::FIELD_KIT_ITEM_LINE_ITEMS => new ArrayCollection([]),
            self::FIELD_CHECKSUM => '',
        ]);
    }

    #[\Override]
    public function getPrice(): ?Price
    {
        return $this->get(self::FIELD_PRICE);
    }

    #[\Override]
    public function getProduct(): Product|VirtualFieldsProductDecorator|null
    {
        return $this->get(self::FIELD_PRODUCT);
    }

    #[\Override]
    public function getProductHolder(): ProductHolderInterface
    {
        return $this->get(self::FIELD_PRODUCT_HOLDER);
    }

    #[\Override]
    public function getProductSku(): ?string
    {
        return $this->get(self::FIELD_PRODUCT_SKU);
    }

    #[\Override]
    public function getEntityIdentifier()
    {
        return $this->get(self::FIELD_ENTITY_IDENTIFIER);
    }

    #[\Override]
    public function getQuantity(): float|int
    {
        return $this->get(self::FIELD_QUANTITY);
    }

    #[\Override]
    public function getProductUnit(): ProductUnit
    {
        return $this->get(self::FIELD_PRODUCT_UNIT);
    }

    #[\Override]
    public function getProductUnitCode(): string
    {
        return $this->get(self::FIELD_PRODUCT_UNIT_CODE);
    }

    #[\Override]
    public function getWeight(): ?Weight
    {
        return $this->get(self::FIELD_WEIGHT);
    }

    #[\Override]
    public function getDimensions(): ?Dimensions
    {
        return $this->get(self::FIELD_DIMENSIONS);
    }

    /**
     * @return Collection<ShippingKitItemLineItem>
     */
    #[\Override]
    public function getKitItemLineItems(): Collection
    {
        return $this->get(self::FIELD_KIT_ITEM_LINE_ITEMS, new ArrayCollection([]));
    }

    #[\Override]
    public function getChecksum(): string
    {
        return $this->get(self::FIELD_CHECKSUM, '');
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

    public function setDimensions(?Dimensions $dimensions): self
    {
        $this->set(self::FIELD_DIMENSIONS, $dimensions);

        return $this;
    }

    public function setWeight(?Weight $weight): self
    {
        $this->set(self::FIELD_WEIGHT, $weight);

        return $this;
    }

    public function setPrice(?Price $price): self
    {
        $this->set(self::FIELD_PRICE, $price);

        return $this;
    }

    public function setKitItemLineItems(Collection $kitItemLineItems): self
    {
        $this->set(self::FIELD_KIT_ITEM_LINE_ITEMS, $kitItemLineItems);

        return $this;
    }

    public function setChecksum(string $checksum): self
    {
        $this->set(self::FIELD_CHECKSUM, $checksum);

        return $this;
    }
}
