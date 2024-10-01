<?php

namespace Oro\Bundle\SEOBundle\Form\Extension;

use Oro\Bundle\ProductBundle\Form\Type\BrandType;

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
