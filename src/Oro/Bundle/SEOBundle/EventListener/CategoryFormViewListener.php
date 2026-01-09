<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

/**
 * Handles form view events for product categories to add SEO metadata fields.
 *
 * This class extends {@see BaseFormViewListener} to provide SEO metadata editing capabilities for product categories.
 * It hooks into the category edit event to inject SEO-related form blocks containing title, description, and keywords
 * fields, allowing administrators to manage SEO metadata for categories.
 */
class CategoryFormViewListener extends BaseFormViewListener
{
    public function onCategoryEdit(BeforeListRenderEvent $event)
    {
        $this->addEditPageBlock($event);
    }

    /**
     * @return string
     */
    #[\Override]
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.catalog.category';
    }
}
