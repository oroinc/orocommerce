<?php

namespace Oro\Bundle\PaymentBundle\Context;

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
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Container of values that represents Payment Kit Item Line Item.
 */
class PaymentKitItemLineItem extends ParameterBag implements
    ProductUnitHolderInterface,
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
            self::FIELD_SORT_ORDER => 0,
        ]);
    }

    #[\Override]
    public function getEntityIdentifier()
    {
        return $this->get(self::FIELD_ENTITY_IDENTIFIER);
    }

    #[\Override]
    public function getProduct(): Product|VirtualFieldsProductDecorator|null
    {
        return $this->get(self::FIELD_PRODUCT);
    }

    #[\Override]
    public function getProductSku(): ?string
    {
        return $this->get(self::FIELD_PRODUCT_SKU);
    }

    #[\Override]
    public function getProductHolder(): ProductHolderInterface
    {
        return $this->get(self::FIELD_PRODUCT_HOLDER);
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
    public function getQuantity(): float|int
    {
        return $this->get(self::FIELD_QUANTITY);
    }

    #[\Override]
    public function getPrice(): ?Price
    {
        return $this->get(self::FIELD_PRICE);
    }

    #[\Override]
    public function getKitItem(): ?ProductKitItem
    {
        return $this->get(self::FIELD_KIT_ITEM);
    }

    #[\Override]
    public function getSortOrder(): int
    {
        return $this->get(self::FIELD_SORT_ORDER, 0);
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
}
