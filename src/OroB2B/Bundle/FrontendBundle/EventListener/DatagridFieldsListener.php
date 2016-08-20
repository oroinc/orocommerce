<?php

namespace Oro\Bundle\FrontendBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityExtendBundle\Grid\AdditionalFieldsExtension;
use Oro\Bundle\EntityExtendBundle\Grid\DynamicFieldsExtension;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\FrontendBundle\Request\FrontendHelperTrait;

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
