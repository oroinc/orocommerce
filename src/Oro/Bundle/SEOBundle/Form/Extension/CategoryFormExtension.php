<?php

namespace Oro\Bundle\SEOBundle\Form\Extension;

use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;

class CategoryFormExtension extends BaseMetaFormExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return CategoryType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.catalog.category';
    }
}
