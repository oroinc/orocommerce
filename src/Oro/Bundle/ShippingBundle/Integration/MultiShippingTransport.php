<?php

namespace Oro\Bundle\ShippingBundle\Integration;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\ShippingBundle\Entity\MultiShippingSettings;
use Oro\Bundle\ShippingBundle\Form\Type\MultiShippingSettingsType;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * The transport for the Multi Shipping Channel.
 */
class MultiShippingTransport implements TransportInterface
{
    private ParameterBag $settings;

    public function init(Transport $transportEntity): void
    {
        $this->settings = $transportEntity->getSettingsBag();
    }

    public function getSettingsFormType(): string
    {
        return MultiShippingSettingsType::class;
    }

    public function getSettingsEntityFQCN(): string
    {
        return MultiShippingSettings::class;
    }

    public function getLabel(): string
    {
        return 'oro.multi_shipping_method.settings.label';
    }
}
