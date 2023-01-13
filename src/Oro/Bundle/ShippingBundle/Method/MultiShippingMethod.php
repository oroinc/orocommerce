<?php

namespace Oro\Bundle\ShippingBundle\Method;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\ShippingBundle\Provider\MultiShippingCostProvider;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Multi Shipping method implementation.
 */
class MultiShippingMethod implements ShippingMethodInterface, ShippingMethodIconAwareInterface
{
    private MultiShippingMethodType $type;
    private string $label;
    private string $icon;
    private string $identifier;
    private bool $enabled;

    public function __construct(
        string $identifier,
        string $label,
        string
        $icon,
        bool $enabled,
        RoundingServiceInterface $roundingService,
        MultiShippingCostProvider $shippingCostProvider
    ) {
        $this->identifier = $identifier;
        $this->label = $label;
        $this->icon = $icon;
        $this->type = new MultiShippingMethodType($label, $roundingService, $shippingCostProvider);
        $this->enabled = $enabled;
    }

    public function isGrouped(): bool
    {
        return false;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getTypes(): array
    {
        return [$this->type];
    }

    public function getType($identifier): ?MultiShippingMethodType
    {
        foreach ($this->getTypes() as $methodType) {
            if ($methodType->getIdentifier() === (string)$identifier) {
                return $methodType;
            }
        }

        return null;
    }

    public function getOptionsConfigurationFormType(): string
    {
        return HiddenType::class;
    }

    public function getSortOrder(): int
    {
        return 10;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }
}
