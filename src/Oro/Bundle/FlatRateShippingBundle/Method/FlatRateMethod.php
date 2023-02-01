<?php

namespace Oro\Bundle\FlatRateShippingBundle\Method;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodIconAwareInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Represents Flat Rate shipping method.
 */
class FlatRateMethod implements ShippingMethodInterface, ShippingMethodIconAwareInterface
{
    private string $identifier;
    private string $label;
    private ?string $icon;
    private bool $enabled;
    private FlatRateMethodType $type;

    public function __construct(string $identifier, string $label, ?string $icon, bool $enabled)
    {
        $this->identifier = $identifier;
        $this->label = $label;
        $this->icon = $icon;
        $this->enabled = $enabled;
        $this->type = new FlatRateMethodType($label);
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * {@inheritDoc}
     */
    public function isGrouped(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * {@inheritDoc}
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * {@inheritDoc}
     */
    public function getTypes(): array
    {
        return [$this->type];
    }

    /**
     * {@inheritDoc}
     */
    public function getType(string $identifier): ?ShippingMethodTypeInterface
    {
        if ($this->type->getIdentifier() === $identifier) {
            return $this->type;
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptionsConfigurationFormType(): ?string
    {
        return HiddenType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getSortOrder(): int
    {
        return 10;
    }
}
