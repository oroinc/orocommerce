<?php

namespace Oro\Bundle\MoneyOrderBundle\Migrations\Data\ORM\Config;

use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;

class MoneyOrderConfigToSettingsConverter
{
    /**
     * @param MoneyOrderConfig $config
     *
     * @return mixed
     */
    public function convert(MoneyOrderConfig $config)
    {
        $settings = new MoneyOrderSettings();

        $settings->addLabel($config->getLabel())
            ->addShortLabel($config->getShortLabel())
            ->setPayTo($config->getPayTo())
            ->setSendTo($config->getSendTo());

        return $settings;
    }
}
