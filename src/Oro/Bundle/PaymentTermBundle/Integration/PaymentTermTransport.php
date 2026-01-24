<?php

namespace Oro\Bundle\PaymentTermBundle\Integration;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;
use Oro\Bundle\PaymentTermBundle\Form\Type\PaymentTermSettingsType;

/**
 * Integration transport for payment term configuration.
 *
 * This transport provider handles the integration of payment term settings with the Oro integration framework,
 * defining the form type and entity class used for storing payment term configuration data.
 */
class PaymentTermTransport implements TransportInterface
{
    #[\Override]
    public function init(Transport $transportEntity)
    {
    }

    #[\Override]
    public function getSettingsFormType()
    {
        return PaymentTermSettingsType::class;
    }

    #[\Override]
    public function getSettingsEntityFQCN()
    {
        return PaymentTermSettings::class;
    }

    #[\Override]
    public function getLabel()
    {
        return 'oro.paymentterm.settings.label';
    }
}
