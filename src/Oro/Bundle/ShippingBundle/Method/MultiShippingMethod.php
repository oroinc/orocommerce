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
    private string $name;
    private string $label;
    private string $icon;
    private bool $enabled;
    private MultiShippingMethodType $type;

    public function __construct(
        string $identifier,
        string $name,
        string $label,
        string $icon,
        bool $enabled,
        RoundingServiceInterface $roundingService,
        MultiShippingCostProvider $shippingCostProvider
    ) {
        $this->identifier = $identifier;
        $this->name = $name;
        $this->label = $label;
        $this->icon = $icon;
        $this->enabled = $enabled;
        $this->type = new MultiShippingMethodType($label, $roundingService, $shippingCostProvider);
    }

    #[\Override]
    public function isGrouped(): bool
    {
        return false;
    }

    #[\Override]
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    #[\Override]
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    #[\Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[\Override]
    public function getLabel(): string
    {
        return $this->label;
    }

    #[\Override]
    public function getTypes(): array
    {
        return [$this->type];
    }

    #[\Override]
    public function getType(string $identifier): ?ShippingMethodTypeInterface
    {
        return $this->type->getIdentifier() === $identifier
            ? $this->type
            : null;
    }

    #[\Override]
    public function getOptionsConfigurationFormType(): ?string
    {
        return null;
    }

    #[\Override]
    public function getSortOrder(): int
    {
        return 10;
    }

    #[\Override]
    public function getIcon(): ?string
    {
        return $this->icon;
    }
}
