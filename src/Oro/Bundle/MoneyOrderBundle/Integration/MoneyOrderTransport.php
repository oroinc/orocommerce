<?php

namespace Oro\Bundle\MoneyOrderBundle\Integration;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;
use Oro\Bundle\MoneyOrderBundle\Form\Type\MoneyOrderSettingsType;

/**
 * Provides transport configuration for Money Order payment method integration.
 *
 * This class handles the integration transport layer for Money Order payments, managing the
 * settings form type and entity class used to store Money Order configuration. It acts as a bridge
 * between the integration framework and Money Order-specific settings, enabling the system to
 * properly initialize and configure Money Order payment channels.
 */
class MoneyOrderTransport implements TransportInterface
{
    #[\Override]
    public function init(Transport $transportEntity)
    {
    }

    #[\Override]
    public function getSettingsFormType()
    {
        return MoneyOrderSettingsType::class;
    }

    #[\Override]
    public function getSettingsEntityFQCN()
    {
        return MoneyOrderSettings::class;
    }

    #[\Override]
    public function getLabel()
    {
        return 'oro.money_order.settings.label';
    }
}
