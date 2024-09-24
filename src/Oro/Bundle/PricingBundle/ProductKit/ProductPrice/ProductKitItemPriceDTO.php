<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\ProductKit\ProductPrice;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;

/**
 * Represents price of a product kit item.
 */
class ProductKitItemPriceDTO extends ProductPriceDTO implements ProductKitItemPriceInterface
{
    public const PRODUCT_KIT_ITEM_ID = 'product_kit_item_id';

    private ProductKitItem $kitItem;

    public function __construct(
        ProductKitItem $productKitItem,
        Product $product,
        Price $price,
        float $quantity,
        MeasureUnitInterface $unit
    ) {
        parent::__construct($product, $price, $quantity, $unit);

        $this->kitItem = $productKitItem;
    }

    #[\Override]
    public function getKitItem(): ProductKitItem
    {
        return $this->kitItem;
    }

    public function setKitItem(ProductKitItem $kitItem): self
    {
        $this->kitItem = $kitItem;

        return $this;
    }

    #[\Override]
    public function toArray(): array
    {
        $array = parent::toArray();
        $array[self::PRODUCT_KIT_ITEM_ID] = $this->kitItem->getId();

        return $array;
    }
}
