<?php

namespace Oro\Bundle\ApruveBundle\Integration;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;

class ApruveTransport implements TransportInterface
{
    /** @var ParameterBag */
    protected $settings;

    /**
    * @param Transport $transportEntity
     */
    public function init(Transport $transportEntity)
    {
         $this->settings = $transportEntity->getSettingsBag();
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsFormType()
    {
        // todo@webevt: change to proper Apruve Settings form type, as soon as it is ready.
        return FormType::class;
    }

     /**
      * {@inheritdoc}
      */
    public function getSettingsEntityFQCN()
    {
        return ApruveSettings::class;
    }

     /**
      * {@inheritdoc}
      */
    public function getLabel()
    {
        return 'oro.apruve.settings.label';
    }
}
