<?php

namespace Oro\Bundle\PaymentTermBundle\Integration;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;
use Oro\Bundle\PaymentTermBundle\Form\Type\PaymentTermSettingsType;

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
