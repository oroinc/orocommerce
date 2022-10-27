<?php

namespace Oro\Bundle\FedexShippingBundle\Model;

/**
 * Represents fedex package settings
 */
interface FedexPackageSettingsInterface
{
    public function getUnitOfWeight(): string;

    public function getDimensionsUnit(): string;

    public function getLimitationExpression(): string;

    public function isDimensionsIgnored(): bool;
}
