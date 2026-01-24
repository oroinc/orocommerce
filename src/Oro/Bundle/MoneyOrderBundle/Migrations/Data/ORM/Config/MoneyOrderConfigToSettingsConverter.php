<?php

namespace Oro\Bundle\MoneyOrderBundle\Migrations\Data\ORM\Config;

use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;

/**
 * Converts Money Order configuration objects to {@see MoneyOrderSettings} entities.
 *
 * This converter transforms {@see MoneyOrderConfig} objects (typically loaded from system configuration)
 * into {@see MoneyOrderSettings} entities during data migrations. It extracts configuration values and
 * populates the corresponding settings entity fields, enabling the migration of Money Order
 * configuration from the legacy system config to the new integration-based settings model.
 */
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
