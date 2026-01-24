<?php

namespace Oro\Bundle\SEOBundle\Form\Extension;

use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;

/**
 * Extends the category form to include SEO metadata fields.
 *
 * This form extension adds SEO metadata editing capabilities to the category form by injecting title, description,
 * and keywords fields. It allows administrators to manage SEO metadata directly from the category edit form.
 */
class CategoryFormExtension extends BaseMetaFormExtension
{
    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [CategoryType::class];
    }

    #[\Override]
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.catalog.category';
    }
}
