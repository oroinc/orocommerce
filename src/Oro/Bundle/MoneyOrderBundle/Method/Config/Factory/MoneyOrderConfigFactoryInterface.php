<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Config\Factory;

use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;

/**
 * Defines the contract for creating Money Order payment method configurations from settings.
 */
interface MoneyOrderConfigFactoryInterface
{
    /**
     * @param MoneyOrderSettings $settings
     * @return MoneyOrderConfigInterface
     */
    public function create(MoneyOrderSettings $settings);
}
