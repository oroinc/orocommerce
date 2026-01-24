<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

/**
 * Handles form view events for CMS pages to add SEO metadata fields.
 *
 * This listener extends the base form view listener to provide SEO metadata editing capabilities for CMS pages.
 * It hooks into the page view and edit events to inject SEO-related form blocks containing title, description,
 * and keywords fields, allowing administrators to manage SEO metadata directly from the page edit form.
 */
class PageFormViewListener extends BaseFormViewListener
{
    public function onPageView(BeforeListRenderEvent $event)
    {
        $this->addViewPageBlock($event);
    }

    public function onPageEdit(BeforeListRenderEvent $event)
    {
        $this->addEditPageBlock($event);
    }

    /**
     * @return string
     */
    #[\Override]
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.cms.page';
    }
}
