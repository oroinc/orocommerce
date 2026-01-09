<?php

namespace Oro\Bundle\FedexShippingBundle\Integration;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Form\Type\FedexIntegrationSettingsType;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;

/**
 * Provides FedEx integration transport configuration.
 *
 * This transport implementation defines the form type and settings entity
 * for FedEx integration channels within the integration framework.
 */
class FedexTransport implements TransportInterface
{
    #[\Override]
    public function init(Transport $transportEntity)
    {
    }

    #[\Override]
    public function getSettingsFormType()
    {
        return FedexIntegrationSettingsType::class;
    }

    #[\Override]
    public function getSettingsEntityFQCN()
    {
        return FedexIntegrationSettings::class;
    }

    #[\Override]
    public function getLabel()
    {
        return 'oro.fedex.integration.settings.label';
    }
}
