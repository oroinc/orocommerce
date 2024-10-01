<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\ProductKit\ProductPrice;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;

/**
 * Represents price of a product kit.
 */
class ProductKitPriceDTO extends ProductPriceDTO implements ProductKitPriceInterface
{
    public const KIT_ITEMS_PRICES = 'kit_items_prices';

    /** @var array<int,ProductKitItemPriceDTO> */
    protected array $kitItemPrices = [];

    public function __construct(
        Product $product,
        Price $price,
        float $quantity,
        MeasureUnitInterface $unit
    ) {
        parent::__construct($product, $price, $quantity, $unit);
    }

    #[\Override]
    public function getKitItemPrices(): array
    {
        return $this->kitItemPrices;
    }

    #[\Override]
    public function getKitItemPrice(ProductKitItem $productKitItem): ?ProductKitItemPriceDTO
    {
        return $this->kitItemPrices[$productKitItem->getId()] ?? null;
    }

    public function addKitItemPrice(ProductKitItemPriceDTO $productKitItemPrice): self
    {
        $this->kitItemPrices[$productKitItemPrice->getKitItem()->getId()] = $productKitItemPrice;

        return $this;
    }

    public function removeKitItemPrice(ProductKitItemPriceDTO $productKitItemPrice): self
    {
        unset($this->kitItemPrices[$productKitItemPrice->getKitItem()->getId()]);

        return $this;
    }

    #[\Override]
    public function toArray(): array
    {
        $array = parent::toArray();
        $array[self::KIT_ITEMS_PRICES] = array_map(
            static fn (ProductKitItemPriceDTO $kitItemPrice) => $kitItemPrice->toArray(),
            $this->kitItemPrices
        );

        return $array;
    }
}
