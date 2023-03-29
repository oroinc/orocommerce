<?php

namespace Oro\Bundle\ProductBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Collection of QuickAddRow models.
 */
class QuickAddRowCollection extends ArrayCollection
{
    /** @var array [['message' => string, 'parameters' => array], ...] */
    private array $errors = [];
    /** @var QuickAddField[] [name => field, ...] */
    private $additionalFields = [];

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

    /**
     * @return Product[]
     */
    public function getProducts(): array
    {
        $products = [];
        /** @var QuickAddRow[] $rows */
        $rows = $this->toArray();
        foreach ($rows as $row) {
            $product = $row->getProduct();
            if (null !== $product) {
                $products[] = $product;
            }
        }

        return $products;
    }

    protected function createFrom(array $elements): static
    {
        $quickAddRowCollection = parent::createFrom($elements);
        $quickAddRowCollection->errors = $this->errors;

        return $quickAddRowCollection;
    }

    public function addError(string $message, array $parameters = []): void
    {
        $this->errors[] = ['message' => $message, 'parameters' => $parameters];
    }

    /**
     * @return array [['message' => string, 'parameters' => array], ...]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function isValid(): bool
    {
        return
            !$this->hasErrors()
            && $this->forAll(function ($key, QuickAddRow $row) {
                return !$row->hasErrors();
            });
    }

    public function addAdditionalField(QuickAddField $field): void
    {
        $this->additionalFields[$field->getName()] = $field;
    }

    /**
     * @return QuickAddField[] [field name => field, ...]
     */
    public function getAdditionalFields(): array
    {
        return $this->additionalFields;
    }

    public function getAdditionalField(string $name): ?QuickAddField
    {
        return $this->additionalFields[$name] ?? null;
    }
}
