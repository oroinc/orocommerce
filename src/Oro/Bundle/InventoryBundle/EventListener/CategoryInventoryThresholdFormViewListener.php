<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\Fallback\AbstractFallbackFieldsFormView;

class CategoryInventoryThresholdFormViewListener extends AbstractFallbackFieldsFormView
{
    /**
     * @param BeforeListRenderEvent $event
     */
    public function onCategoryEdit(BeforeListRenderEvent $event)
    {
        $this->addBlockToEntityEdit(
            $event,
            'OroInventoryBundle:Category:editInventoryThreshold.html.twig',
            'oro.catalog.sections.default_options'
        );
    }
}
