<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

/**
 * Handles form view events for product brands to add SEO metadata fields.
 *
 * This listener extends {@see BaseFormViewListener} to provide SEO metadata editing capabilities for product brands.
 * It hooks into the brand edit event to inject SEO-related form blocks containing title, description, and keywords
 * fields, allowing administrators to manage SEO metadata for brands.
 */
class BrandFormViewListener extends BaseFormViewListener
{
    public function onBrandEdit(BeforeListRenderEvent $event)
    {
        $this->addEditPageBlock($event);
    }

    /**
     * @return string
     */
    #[\Override]
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.product.brand';
    }
}
