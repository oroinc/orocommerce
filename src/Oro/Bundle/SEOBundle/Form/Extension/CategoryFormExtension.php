<?php

namespace Oro\Bundle\SEOBundle\Form\Extension;

use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;

class CategoryFormExtension extends BaseMetaFormExtension
{
    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [CategoryType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.catalog.category';
    }
}
