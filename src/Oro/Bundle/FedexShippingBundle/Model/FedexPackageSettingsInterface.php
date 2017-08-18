<?php

namespace Oro\Bundle\FedexShippingBundle\Model;

interface FedexPackageSettingsInterface
{
    /**
     * @return float
     */
    public function getMaxWeight(): float;

    /**
     * @return float
     */
    public function getMaxLength(): float;

    /**
     * @return float
     */
    public function getMaxGirth(): float;

    /**
     * @return string
     */
    public function getUnitOfWeight(): string;

    /**
     * @return string
     */
    public function getDimensionsUnit(): string;
}
