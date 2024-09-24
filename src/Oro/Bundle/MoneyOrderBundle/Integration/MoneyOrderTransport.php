<?php

namespace Oro\Bundle\MoneyOrderBundle\Integration;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;
use Oro\Bundle\MoneyOrderBundle\Form\Type\MoneyOrderSettingsType;

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
