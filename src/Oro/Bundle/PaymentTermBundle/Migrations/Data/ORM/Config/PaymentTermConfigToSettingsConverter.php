<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Data\ORM\Config;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;

/**
 * Converts legacy payment term configuration to payment term settings.
 *
 * This converter transforms {@see PaymentTermConfig} objects (containing legacy system configuration values)
 * into {@see PaymentTermSettings} entities used by new integration-based payment term system during data migrations.
 */
class PaymentTermConfigToSettingsConverter
{
    /**
     * @param PaymentTermConfig $config
     *
     * @return mixed
     */
    public function convert(PaymentTermConfig $config)
    {
        $settings = new PaymentTermSettings();

        $settings->addLabel($config->getLabel())
            ->addShortLabel($config->getShortLabel());

        return $settings;
    }
}
