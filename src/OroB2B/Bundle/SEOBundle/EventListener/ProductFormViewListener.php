<?php

namespace OroB2B\Bundle\SEOBundle\EventListener;

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
        $this->addViewPageBlock($event, 'OroB2BProductBundle:Product');
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
    public function getExtendedEntitySuffix()
    {
        return 'product';
    }
}
