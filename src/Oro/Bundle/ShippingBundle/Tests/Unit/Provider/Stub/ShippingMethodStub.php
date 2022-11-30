<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub;

use Oro\Bundle\FormBundle\Form\Type\OroUnstructuredHiddenType;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodIconAwareInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;

class ShippingMethodStub implements ShippingMethodInterface, ShippingMethodIconAwareInterface
{
    /** @var ShippingMethodTypeStub[] */
    private array $types = [];
    private ?string $identifier = null;
    private ?string $label = null;
    private ?int $sortOrder = null;
    private string $optionsConfigurationFormType = OroUnstructuredHiddenType::class;
    private bool $isEnabled = true;
    private bool $isGrouped = false;
    private ?string $icon = null;

    /**
     * {@inheritDoc}
     */
    public function getTypes()
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
    public function getType($identifier)
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
    public function getIdentifier()
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
    public function getSortOrder()
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
    public function getOptionsConfigurationFormType()
    {
        return $this->optionsConfigurationFormType;
    }

    public function setOptionsConfigurationFormType(string $optionsConfigurationFormType): void
    {
        $this->optionsConfigurationFormType = $optionsConfigurationFormType;
    }

    /**
     * {@inheritDoc}
     */
    public function isGrouped()
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
    public function isEnabled()
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
    public function getIcon()
    {
        return $this->icon;
    }

    public function setIcon(string $icon): void
    {
        $this->icon = $icon;
    }
}
