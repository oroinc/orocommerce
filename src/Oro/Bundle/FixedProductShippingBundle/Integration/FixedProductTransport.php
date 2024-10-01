<?php

namespace Oro\Bundle\FixedProductShippingBundle\Integration;

use Oro\Bundle\FixedProductShippingBundle\Entity\FixedProductSettings;
use Oro\Bundle\FixedProductShippingBundle\Form\Type\FixedProductSettingsType;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * The transport for the Fixed Product Channel.
 */
class FixedProductTransport implements TransportInterface
{
    private ParameterBag $settings;

    #[\Override]
    public function init(Transport $transportEntity): void
    {
        $this->settings = $transportEntity->getSettingsBag();
    }

    #[\Override]
    public function getSettingsFormType(): string
    {
        return FixedProductSettingsType::class;
    }

    #[\Override]
    public function getSettingsEntityFQCN(): string
    {
        return FixedProductSettings::class;
    }

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.fixed_product.settings.label';
    }
}
