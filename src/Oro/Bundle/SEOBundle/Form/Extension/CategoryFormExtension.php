<?php

namespace Oro\Bundle\SEOBundle\Form\Extension;

use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;

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
