<?php

namespace Oro\Bundle\SEOBundle\Form\Extension;

use Oro\Bundle\CMSBundle\Form\Type\PageType;

/**
 * Extends the CMS page form to include SEO metadata fields.
 *
 * This form extension adds SEO metadata editing capabilities to the CMS page form by injecting title, description,
 * and keywords fields. It allows administrators to manage SEO metadata directly from the page edit form.
 */
class PageFormExtension extends BaseMetaFormExtension
{
    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [PageType::class];
    }

    #[\Override]
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.cms.page';
    }
}
