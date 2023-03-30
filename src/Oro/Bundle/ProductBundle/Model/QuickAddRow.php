<?php

namespace Oro\Bundle\ProductBundle\Model;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * A model that represents a row in {@see QuickAddRowCollection}.
 */
class QuickAddRow implements ProductHolderInterface, QuantityAwareInterface
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

    public function getEntityIdentifier(): ?int
    {
        return $this->index;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function setQuantity(float $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getProductSku(): ?string
    {
        return $this->product?->getSku();
    }

    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    public function setOrganization(?string $organization): void
    {
        $this->organization = $organization;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): void
    {
        $this->unit = $unit;
    }

    public function addError(string $errorMessage, array $additionalParameters = [], string $propertyPath = ''): void
    {
        $this->errors[] = [
            'message' => $errorMessage,
            'parameters' => array_merge($additionalParameters, [
                '{{ index }}' => $this->index,
                '{{ sku }}' => $this->sku
            ]),
            'propertyPath' => $propertyPath ?? '',
        ];
    }

    /**
     * @return array [['message' => string, 'parameters' => array, 'propertyPath' => string], ...]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
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
