<?php

namespace OroB2B\Bundle\FrontendBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityExtendBundle\Grid\AdditionalFieldsExtension;
use Oro\Bundle\EntityExtendBundle\Grid\DynamicFieldsExtension;

class DatagridFieldsListener extends AbstractFrontendDatagridListener
{
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
