<?php

namespace Oro\Bundle\FedexShippingBundle\Model;

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
}
