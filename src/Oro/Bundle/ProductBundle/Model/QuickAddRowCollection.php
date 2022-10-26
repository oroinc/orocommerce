<?php

namespace Oro\Bundle\ProductBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Collection of QuickAddRow models.
 */
class QuickAddRowCollection extends ArrayCollection
{
    use QuickAddFieldTrait;

    protected array $errors = [];

    public function __toString(): string
    {
        return implode(PHP_EOL, $this->map(function (QuickAddRow $row) {
            return sprintf('%s, %s', $row->getSku(), $row->getQuantity());
        })->toArray());
    }

    public function getValidRows(): QuickAddRowCollection
    {
        return $this->filter(function (QuickAddRow $row) {
            return !$row->hasErrors();
        });
    }

    public function getInvalidRows(): QuickAddRowCollection
    {
        return $this->filter(function (QuickAddRow $row) {
            return $row->hasErrors();
        });
    }

    public function getSkus(): array
    {
        $skus = [];

        /** @var QuickAddRow $row */
        foreach ($this->getIterator() as $row) {
            if ($sku = $row->getSku()) {
                $skus[] = $sku;
            }
        }

        return $skus;
    }

    /**
     * @param Product[] $products
     *
     * @return QuickAddRowCollection
     */
    public function mapProducts(array $products): QuickAddRowCollection
    {
        /** @var QuickAddRow $row */
        foreach ($this->getIterator() as $row) {
            $sku = mb_strtoupper($row->getSku());

            if (array_key_exists($sku, $products)) {
                $row->setProduct($products[$sku]);
            }
        }

        return $this;
    }

    public function getProducts(): array
    {
        $products = [];

        /** @var QuickAddRow $row */
        foreach ($this->getIterator() as $row) {
            if ($product = $row->getProduct()) {
                $products[mb_strtoupper($product->getSku())] = $product;
            }
        }

        return $products;
    }

    protected function createFrom(array $elements): QuickAddRowCollection
    {
        $quickAddRowCollection = parent::createFrom($elements);
        $quickAddRowCollection->errors = $this->errors;

        return $quickAddRowCollection;
    }

    public function addError(string $message, array $parameters = []): self
    {
        $this->errors[] = [
            'message' => $message,
            'parameters' => $parameters,
        ];

        return $this;
    }

    /**
     * @return array<array{message: string, parameters: array}>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }
}
