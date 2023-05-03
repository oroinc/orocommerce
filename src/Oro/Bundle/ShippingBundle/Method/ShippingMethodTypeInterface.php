<?php

namespace Oro\Bundle\ShippingBundle\Method;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

/**
 * Represents a shipping method type.
 */
interface ShippingMethodTypeInterface
{
    public function getIdentifier(): string;

    public function getLabel(): string;

    public function getSortOrder(): int;

    public function getOptionsConfigurationFormType(): ?string;

    public function calculatePrice(
        ShippingContextInterface $context,
        array $methodOptions,
        array $typeOptions
    ): ?Price;
}
