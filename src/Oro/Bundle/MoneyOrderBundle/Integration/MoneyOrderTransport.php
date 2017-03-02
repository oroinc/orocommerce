<?php

namespace Oro\Bundle\MoneyOrderBundle\Integration;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;
use Oro\Bundle\MoneyOrderBundle\Form\Type\MoneyOrderSettingsType;

class MoneyOrderTransport implements TransportInterface
{
    /**
     * {@inheritDoc}
     */
    public function init(Transport $transportEntity)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getSettingsFormType()
    {
        return MoneyOrderSettingsType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getSettingsEntityFQCN()
    {
        return MoneyOrderSettings::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        return 'oro.money_order.settings.label';
    }
}
