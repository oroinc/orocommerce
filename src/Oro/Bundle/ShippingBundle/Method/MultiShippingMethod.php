<?php

namespace Oro\Bundle\ShippingBundle\Method;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\ShippingBundle\Provider\MultiShippingCostProvider;

/**
 * Represents Multi Shipping method.
 */
class MultiShippingMethod implements ShippingMethodInterface, ShippingMethodIconAwareInterface
{
    private string $identifier;
    private string $label;
    private string $icon;
    private bool $enabled;
    private MultiShippingMethodType $type;

    public function __construct(
        string $identifier,
        string $label,
        string $icon,
        bool $enabled,
        RoundingServiceInterface $roundingService,
        MultiShippingCostProvider $shippingCostProvider
    ) {
        $this->identifier = $identifier;
        $this->label = $label;
        $this->icon = $icon;
        $this->enabled = $enabled;
        $this->type = new MultiShippingMethodType($label, $roundingService, $shippingCostProvider);
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
    public function getIdentifier(): string
    {
        return $this->identifier;
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
    public function getTypes(): array
    {
        return [$this->type];
    }

    /**
     * {@inheritDoc}
     */
    public function getType(string $identifier): ?ShippingMethodTypeInterface
    {
        return $this->type->getIdentifier() === $identifier
            ? $this->type
            : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptionsConfigurationFormType(): ?string
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getSortOrder(): int
    {
        return 10;
    }

    /**
     * {@inheritDoc}
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }
}
