<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

class ContentNodeFormViewListener extends BaseFormViewListener
{
    /**
     * @param BeforeListRenderEvent $event
     */
    public function onContentNodeView(BeforeListRenderEvent $event)
    {
        $this->addViewPageBlock($event, ContentNode::class);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onContentNodeEdit(BeforeListRenderEvent $event)
    {
        $this->addEditPageBlock($event);
    }

    /**
     * @return string
     */
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.webcatalog.contentnode';
    }
}
