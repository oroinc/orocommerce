<?php

namespace Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * A model for storing product kit item price criteria data.
 */
class ProductKitItemPriceCriteria extends ProductPriceCriteria
{
    protected ?ProductKitItem $kitItem;

    private ?string $identifier = null;

    public function __construct(
        ?ProductKitItem $productKitItem = null,
        ?Product $product = null,
        ?ProductUnit $productUnit = null,
        ?float $quantity = null,
        ?string $currency = null
    ) {
        $this->kitItem = $productKitItem;

        parent::__construct($product, $productUnit, $quantity, $currency);
    }

    #[\Override]
    protected function assertIsValid(): void
    {
        if (!$this->kitItem?->getId()) {
            throw new \InvalidArgumentException('ProductKitItem must have an id.');
        }

        parent::assertIsValid();
    }

    #[\Override]
    public function getIdentifier(): string
    {
        if (!$this->identifier) {
            $this->identifier = sprintf(
                '%s-%s-%s-%s-%s',
                $this->getKitItem()->getId(),
                $this->getProduct()->getId(),
                $this->getProductUnit()->getCode(),
                $this->getQuantity(),
                $this->getCurrency()
            );
        }

        return $this->identifier;
    }

    public function getKitItem(): ProductKitItem
    {
        return $this->kitItem;
    }
}
