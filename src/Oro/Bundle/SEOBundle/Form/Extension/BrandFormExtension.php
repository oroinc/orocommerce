<?php

namespace Oro\Bundle\SEOBundle\Form\Extension;

use Oro\Bundle\ProductBundle\Form\Type\BrandType;

/**
 * Extends the brand form to include SEO metadata fields.
 *
 * This form extension adds SEO metadata editing capabilities to the brand form by injecting title, description,
 * and keywords fields. It allows administrators to manage SEO metadata directly from the brand edit form.
 */
class BrandFormExtension extends BaseMetaFormExtension
{
    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [BrandType::class];
    }

    #[\Override]
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.product.brand';
    }
}
