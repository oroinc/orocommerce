<?php

namespace Oro\Bundle\ProductBundle\Model;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

/**
 * A model that represents a row in {@see QuickAddRowCollection}.
 */
class QuickAddRow implements ProductHolderInterface, QuantityAwareInterface, ProductUnitHolderInterface
{
    public const INDEX = 'index';
    public const SKU = 'sku';
    public const UNIT = 'unit';
    public const QUANTITY = 'quantity';
    public const ORGANIZATION = 'organization';

    private int $index;
    private string $sku;
    private float $quantity;
    private ?Product $product = null;
    private ?string $unit;
    private ?string $organization;
    /** @var array [['message' => string, 'parameters' => array, 'propertyPath' => string], ...] */
    private array $errors = [];
    /** @var array [['message' => string, 'parameters' => array, 'propertyPath' => string], ...] */
    private array $warnings = [];
    /** @var QuickAddField[] [name => field, ...] */
    private $additionalFields = [];

    public function __construct(
        int $index,
        string $sku,
        float $quantity,
        ?string $unit = null,
        ?string $organization = null
    ) {
        $this->index = $index;
        $this->sku = $sku;
        $this->quantity = $quantity;
        $this->unit = $unit;
        $this->organization = $organization;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    #[\Override]
    public function getEntityIdentifier(): ?int
    {
        return $this->index;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    #[\Override]
    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function setQuantity(float $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    #[\Override]
    public function getProductSku(): ?string
    {
        return $this->product?->getSku();
    }

    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    public function setOrganization(?string $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    #[\Override]
    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    public function addError(string $errorMessage, array $additionalParameters = [], string $propertyPath = ''): self
    {
        $this->errors[] = [
            'message' => $errorMessage,
            'parameters' => array_merge($additionalParameters, [
                '{{ index }}' => $this->index,
                '{{ sku }}' => $this->sku
            ]),
            'propertyPath' => $propertyPath ?? '',
        ];

        return $this;
    }

    public function addWarning(string $errorMessage, array $additionalParameters = [], string $propertyPath = ''): self
    {
        $this->warnings[] = [
            'message' => $errorMessage,
            'parameters' => array_merge($additionalParameters, [
                '{{ index }}' => $this->index,
                '{{ sku }}' => $this->sku
            ]),
            'propertyPath' => $propertyPath ?? '',
        ];

        return $this;
    }

    /**
     * @return array [['message' => string, 'parameters' => array, 'propertyPath' => string], ...]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array [['message' => string, 'parameters' => array, 'propertyPath' => string], ...]
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    public function addAdditionalField(QuickAddField $field): self
    {
        $this->additionalFields[$field->getName()] = $field;

        return $this;
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

    #[\Override]
    public function getProductHolder(): ?Product
    {
        return $this->product;
    }

    #[\Override]
    public function getProductUnit(): ?ProductUnit
    {
        return $this->getProductUnitPrecision()?->getUnit();
    }

    public function getProductUnitPrecision(): ?ProductUnitPrecision
    {
        if (!$this->product || !$this->unit) {
            return null;
        }

        return $this->product->getUnitPrecision($this->unit);
    }

    #[\Override]
    public function getProductUnitCode(): ?string
    {
        return $this->unit;
    }
}
