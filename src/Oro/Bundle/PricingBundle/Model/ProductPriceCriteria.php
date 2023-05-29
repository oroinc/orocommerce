<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * A model for storing product price criteria data.
 */
class ProductPriceCriteria
{
    protected ?Product $product;

    protected ?ProductUnit $productUnit;

    protected ?float $quantity;

    protected ?string $currency;

    private ?string $identifier = null;

    public function __construct(
        ?Product $product = null,
        ?ProductUnit $productUnit = null,
        ?float $quantity = null,
        ?string $currency = null
    ) {
        $this->product = $product;
        $this->productUnit = $productUnit;
        $this->quantity = $quantity;
        $this->currency = $currency;

        $this->assertIsValid();
    }

    protected function assertIsValid(): void
    {
        if (!$this->product?->getId()) {
            throw new \InvalidArgumentException('Product must have id.');
        }

        if (!$this->productUnit?->getCode()) {
            throw new \InvalidArgumentException('ProductUnit must have code.');
        }

        if ($this->quantity === null || $this->quantity < 0) {
            throw new \InvalidArgumentException('Quantity must be numeric and more than or equal zero.');
        }

        if (!$this->currency) {
            throw new \InvalidArgumentException('Currency must be non-empty string.');
        }
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getProductUnit(): ProductUnit
    {
        return $this->productUnit;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getIdentifier(): string
    {
        if (!isset($this->identifier)) {
            $this->identifier = sprintf(
                '%s-%s-%s-%s',
                $this->getProduct()->getId(),
                $this->getProductUnit()->getCode(),
                $this->getQuantity(),
                $this->getCurrency()
            );
        }

        return $this->identifier;
    }
}
