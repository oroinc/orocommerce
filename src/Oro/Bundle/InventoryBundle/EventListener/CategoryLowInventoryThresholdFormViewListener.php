<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\Fallback\AbstractFallbackFieldsFormView;

class CategoryLowInventoryThresholdFormViewListener extends AbstractFallbackFieldsFormView
{
    public function onCategoryEdit(BeforeListRenderEvent $event)
    {
        $category = $this->getEntityFromRequest(Category::class);
        if ($category === null) {
            return;
        }

        $this->addBlockToEntityEdit(
            $event,
            'OroInventoryBundle:Category:editLowInventoryThreshold.html.twig',
            'oro.catalog.sections.default_options'
        );
    }
}
