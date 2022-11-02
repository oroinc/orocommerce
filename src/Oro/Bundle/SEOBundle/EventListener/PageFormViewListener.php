<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

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
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.cms.page';
    }
}
