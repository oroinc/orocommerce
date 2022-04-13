<?php

namespace Oro\Bundle\FixedProductShippingBundle\Method;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FixedProductShippingBundle\Provider\ShippingCostProvider;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodIconAwareInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Fixed Product Shipping method implementation.
 */
class FixedProductMethod implements ShippingMethodInterface, ShippingMethodIconAwareInterface
{
    private FixedProductMethodType $type;
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
        ShippingCostProvider $shippingCostProvider
    ) {
        $this->identifier = $identifier;
        $this->label = $label;
        $this->icon = $icon;
        $this->type = new FixedProductMethodType($label, $roundingService, $shippingCostProvider);
        $this->enabled = $enabled;
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
    public function getType($identifier): ?FixedProductMethodType
    {
        foreach ($this->getTypes() as $methodType) {
            if ($methodType->getIdentifier() === (string)$identifier) {
                return $methodType;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptionsConfigurationFormType(): string
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
    public function getIcon(): string
    {
        return $this->icon;
    }
}
