<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class PageFormViewListener extends BaseFormViewListener
{
    /**
     * @param BeforeListRenderEvent $event
     */
    public function onPageView(BeforeListRenderEvent $event)
    {
        $this->addViewPageBlock($event, 'OroCMSBundle:Page');
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onPageEdit(BeforeListRenderEvent $event)
    {
        $this->addEditPageBlock($event);
    }

    /**
     * @return string
     */
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.cms.page';
    }
}
