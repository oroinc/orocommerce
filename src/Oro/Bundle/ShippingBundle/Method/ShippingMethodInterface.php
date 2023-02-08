<?php

namespace Oro\Bundle\ShippingBundle\Method;

/**
 * Represents a shipping method.
 */
interface ShippingMethodInterface
{
    public function isGrouped(): bool;

    public function isEnabled(): bool;

    public function getIdentifier(): string;

    public function getLabel(): string;

    /**
     * @return ShippingMethodTypeInterface[]
     */
    public function getTypes(): array;

    public function getType(string $identifier): ?ShippingMethodTypeInterface;

    public function getOptionsConfigurationFormType(): ?string;

    public function getSortOrder(): int;
}
