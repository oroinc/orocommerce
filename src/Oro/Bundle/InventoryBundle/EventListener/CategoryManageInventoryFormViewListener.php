<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\Fallback\AbstractFallbackFieldsFormView;

/**
 * Adds manage inventory information to the category edit page.
 */
class CategoryManageInventoryFormViewListener extends AbstractFallbackFieldsFormView
{
    public function onCategoryEdit(BeforeListRenderEvent $event)
    {
        $category = $this->getEntityFromRequest(Category::class);
        if ($category === null) {
            return;
        }

        $this->addBlockToEntityEdit(
            $event,
            '@OroInventory/Category/editManageInventory.html.twig',
            'oro.catalog.sections.default_options'
        );
    }
}
