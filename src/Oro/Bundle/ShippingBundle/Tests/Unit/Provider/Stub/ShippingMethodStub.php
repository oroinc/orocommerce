<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub;

use Oro\Bundle\FormBundle\Form\Type\OroUnstructuredHiddenType;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodIconAwareInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

class ShippingMethodStub implements ShippingMethodInterface, ShippingMethodIconAwareInterface
{
    private bool $isGrouped = false;
    private bool $isEnabled = true;
    private string $identifier;
    private string $label = '';
    /** @var ShippingMethodTypeStub[] */
    private array $types = [];
    private ?string $optionsConfigurationFormType = OroUnstructuredHiddenType::class;
    private int $sortOrder;
    private ?string $icon = null;

    /**
     * {@inheritDoc}
     */
    public function isGrouped(): bool
    {
        return $this->isGrouped;
    }

    public function setIsGrouped(bool $isGrouped): void
    {
        $this->isGrouped = $isGrouped;
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(bool $isEnabled): void
    {
        $this->isEnabled = $isEnabled;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return $this->label ?: $this->identifier . '.label';
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * {@inheritDoc}
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @param ShippingMethodTypeStub[] $types
     */
    public function setTypes(array $types): void
    {
        $this->types = $types;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(string $identifier): ?ShippingMethodTypeInterface
    {
        foreach ($this->types as $type) {
            if ($type->getIdentifier() === $identifier) {
                return $type;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptionsConfigurationFormType(): ?string
    {
        return $this->optionsConfigurationFormType;
    }

    public function setOptionsConfigurationFormType(?string $optionsConfigurationFormType): void
    {
        $this->optionsConfigurationFormType = $optionsConfigurationFormType;
    }

    /**
     * {@inheritDoc}
     */
    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }

    /**
     * {@inheritDoc}
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): void
    {
        $this->icon = $icon;
    }
}
