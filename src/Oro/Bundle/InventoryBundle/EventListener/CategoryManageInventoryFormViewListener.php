<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\InventoryBundle\Fallback\AbstractFallbackFieldsFormView;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class CategoryManageInventoryFormViewListener extends AbstractFallbackFieldsFormView
{
    /**
     * @param BeforeListRenderEvent $event
     */
    public function onCategoryEdit(BeforeListRenderEvent $event)
    {
        $this->onEntityEdit(
            $event,
            'OroInventoryBundle:Category:editManageInventory.html.twig',
            'oro.catalog.sections.default_options'
        );
    }
}
