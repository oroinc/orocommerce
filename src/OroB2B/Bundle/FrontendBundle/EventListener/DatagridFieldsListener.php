<?php

namespace OroB2B\Bundle\FrontendBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityExtendBundle\Grid\AdditionalFieldsExtension;
use Oro\Bundle\EntityExtendBundle\Grid\DynamicFieldsExtension;

use OroB2B\Bundle\FrontendBundle\Request\FrontendHelper;
use OroB2B\Bundle\FrontendBundle\Request\FrontendHelperTrait;

class DatagridFieldsListener
{
    use FrontendHelperTrait;

    /**
     * @param FrontendHelper $frontendHelper
     */
    public function __construct(FrontendHelper $frontendHelper)
    {
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();
        if (!$this->isFrontendRequest()) {
            return;
        }

        $config->offsetSetByPath(AdditionalFieldsExtension::ADDITIONAL_FIELDS_CONFIG_PATH, []);
        $config->offsetSetByPath(DynamicFieldsExtension::EXTEND_ENTITY_CONFIG_PATH, false);
    }
}
