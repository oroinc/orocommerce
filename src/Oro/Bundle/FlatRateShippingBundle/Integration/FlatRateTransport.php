<?php

namespace Oro\Bundle\FlatRateShippingBundle\Integration;

use Oro\Bundle\FlatRateShippingBundle\Entity\FlatRateSettings;
use Oro\Bundle\FlatRateShippingBundle\Form\Type\FlatRateSettingsType;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Transport implementation for flat rate shipping integration.
 *
 * Provides transport configuration and settings management for the flat rate shipping
 * integration channel.
 */
class FlatRateTransport implements TransportInterface
{
    /** @var ParameterBag */
    protected $settings;

    #[\Override]
    public function init(Transport $transportEntity)
    {
        $this->settings = $transportEntity->getSettingsBag();
    }

    #[\Override]
    public function getSettingsFormType()
    {
        return FlatRateSettingsType::class;
    }

    #[\Override]
    public function getSettingsEntityFQCN()
    {
        return FlatRateSettings::class;
    }

    #[\Override]
    public function getLabel()
    {
        return 'oro.flat_rate.settings.label';
    }
}
