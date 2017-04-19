<?php

namespace Oro\Bundle\MoneyOrderBundle\Method\Config\Factory;

use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;

interface MoneyOrderConfigFactoryInterface
{
    /**
     * @param MoneyOrderSettings $settings
     * @return MoneyOrderConfigInterface
     */
    public function create(MoneyOrderSettings $settings);
}
