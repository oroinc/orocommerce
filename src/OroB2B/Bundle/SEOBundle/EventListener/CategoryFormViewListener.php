<?php

namespace OroB2B\Bundle\SEOBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class CategoryFormViewListener extends BaseFormViewListener
{
    /**
     * @param BeforeListRenderEvent $event
     */
    public function onCategoryEdit(BeforeListRenderEvent $event)
    {
        $this->addEditPageBlock($event);
    }

    /**
     * @return string
     */
    public function getMetaFieldLabelPrefix()
    {
        return 'orob2b.catalog.category';
    }
}
