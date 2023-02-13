<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

class ShippingMethodTypeStub implements ShippingMethodTypeInterface
{
    private string $identifier;
    private string $label = '';
    private int $sortOrder;
    private ?string $optionsConfigurationFormType = ShippingMethodTypeConfigTypeOptionsStub::class;

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
    public function getOptionsConfigurationFormType(): ?string
    {
        return $this->optionsConfigurationFormType;
    }

    public function setOptionsConfigurationFormType(?string $optionsConfigurationFormType): void
    {
        $this->optionsConfigurationFormType = $optionsConfigurationFormType;
    }

    /**
     * {@inheritdoc}
     */
    public function calculatePrice(
        ShippingContextInterface $context,
        array $methodOptions,
        array $typeOptions
    ): ?Price {
        return $typeOptions['price'];
    }
}
