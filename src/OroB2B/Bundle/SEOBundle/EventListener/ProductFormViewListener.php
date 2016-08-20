<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

class ProductFormViewListener extends BaseFormViewListener
{
    /**
     * Insert SEO information
     *
     * @param BeforeListRenderEvent $event
     */
    public function onProductView(BeforeListRenderEvent $event)
    {
        $this->addViewPageBlock($event, 'OroProductBundle:Product');
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onProductEdit(BeforeListRenderEvent $event)
    {
        $this->addEditPageBlock($event);
    }


    /**
     * @return string
     */
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.product';
    }
}
