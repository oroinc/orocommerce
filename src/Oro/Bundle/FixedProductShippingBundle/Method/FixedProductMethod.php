<?php

namespace Oro\Bundle\FixedProductShippingBundle\Method;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FixedProductShippingBundle\Provider\ShippingCostProvider;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodIconAwareInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Represents Fixed Product shipping method.
 */
class FixedProductMethod implements ShippingMethodInterface, ShippingMethodIconAwareInterface
{
    private string $identifier;
    private string $label;
    private string $icon;
    private bool $enabled;
    private FixedProductMethodType $type;

    public function __construct(
        string $identifier,
        string $label,
        string $icon,
        bool $enabled,
        RoundingServiceInterface $roundingService,
        ShippingCostProvider $shippingCostProvider
    ) {
        $this->identifier = $identifier;
        $this->label = $label;
        $this->icon = $icon;
        $this->enabled = $enabled;
        $this->type = new FixedProductMethodType($label, $roundingService, $shippingCostProvider);
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
        return HiddenType::class;
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
