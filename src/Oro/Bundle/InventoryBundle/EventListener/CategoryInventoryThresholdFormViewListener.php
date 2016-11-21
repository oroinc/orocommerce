<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\InventoryBundle\Fallback\AbstractFallbackFieldsFormView;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class CategoryInventoryThresholdFormViewListener extends AbstractFallbackFieldsFormView
{
    /**
     * @param BeforeListRenderEvent $event
     */
    public function onCategoryEdit(BeforeListRenderEvent $event)
    {
        $this->onEntityEdit(
            $event,
            'OroInventoryBundle:Category:editInventoryThreshold.html.twig',
            'oro.catalog.sections.default_options'
        );
    }
}
