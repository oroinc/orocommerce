<?php

namespace Oro\Bundle\ApruveBundle\Integration;

use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;
use Oro\Bundle\ApruveBundle\Form\Type\ApruveSettingsType;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class ApruveTransport implements TransportInterface
{
    /**
     * @var ParameterBag
     */
    protected $settings;

    /**
    * @param Transport $transportEntity
     */
    public function init(Transport $transportEntity)
    {
        $this->settings = $transportEntity->getSettingsBag();
    }

    /**
     * {@inheritDoc}
     */
    public function getSettingsFormType()
    {
        return ApruveSettingsType::class;
    }

     /**
      * {@inheritDoc}
      */
    public function getSettingsEntityFQCN()
    {
        return ApruveSettings::class;
    }

     /**
      * {@inheritDoc}
      */
    public function getLabel()
    {
        return 'oro.apruve.settings.label';
    }
}
