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
    private string $name;
    private string $label;
    private ?string $icon;
    private bool $enabled;
    private FlatRateMethodType $type;

    public function __construct(string $identifier, string $name, string $label, ?string $icon, bool $enabled)
    {
        $this->identifier = $identifier;
        $this->name = $name;
        $this->label = $label;
        $this->icon = $icon;
        $this->enabled = $enabled;
        $this->type = new FlatRateMethodType($label);
    }

    #[\Override]
    public function getIdentifier(): string
    {
        return $this->identifier;
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
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    #[\Override]
    public function getTypes(): array
    {
        return [$this->type];
    }

    #[\Override]
    public function getType(string $identifier): ?ShippingMethodTypeInterface
    {
        if ($this->type->getIdentifier() === $identifier) {
            return $this->type;
        }

        return null;
    }

    #[\Override]
    public function getOptionsConfigurationFormType(): ?string
    {
        return HiddenType::class;
    }

    #[\Override]
    public function getSortOrder(): int
    {
        return 10;
    }
}
