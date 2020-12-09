<?php

namespace Oro\Bundle\FedexShippingBundle\Model;

/**
 * Represents fedex package settings
 */
interface FedexPackageSettingsInterface
{
    /**
     * @return string
     */
    public function getUnitOfWeight(): string;

    /**
     * @return string
     */
    public function getDimensionsUnit(): string;

    /**
     * @return string
     */
    public function getLimitationExpression(): string;

    /**
     * @return bool
     */
    public function isDimensionsIgnored(): bool;
}
