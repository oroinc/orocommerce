<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Data\ORM\Config;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;

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
