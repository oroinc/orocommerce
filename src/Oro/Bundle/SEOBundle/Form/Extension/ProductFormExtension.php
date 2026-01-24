<?php

namespace Oro\Bundle\SEOBundle\Form\Extension;

use Oro\Bundle\ProductBundle\Form\Type\ProductType;

/**
 * Extends the product form to include SEO metadata fields.
 *
 * This form extension adds SEO metadata editing capabilities to the product form by injecting title, description,
 * and keywords fields. It allows administrators to manage SEO metadata directly from the product edit form.
 */
class ProductFormExtension extends BaseMetaFormExtension
{
    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [ProductType::class];
    }

    #[\Override]
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.product';
    }
}
