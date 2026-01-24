<?php

namespace Oro\Bundle\SEOBundle\Form\Extension;

use Oro\Bundle\WebCatalogBundle\Form\Type\ContentNodeType;

/**
 * Extends the content node form to include SEO metadata fields.
 *
 * This form extension adds SEO metadata editing capabilities to the content node form by injecting title, description,
 * and keywords fields. It allows administrators to manage SEO metadata directly from the content node edit form.
 */
class ContentNodeFormExtension extends BaseMetaFormExtension
{
    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [ContentNodeType::class];
    }

    #[\Override]
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.webcatalog.contentnode';
    }
}
