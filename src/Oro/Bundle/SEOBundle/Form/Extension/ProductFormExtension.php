<?php

namespace Oro\Bundle\SEOBundle\Form\Extension;

use Oro\Bundle\ProductBundle\Form\Type\ProductType;

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
