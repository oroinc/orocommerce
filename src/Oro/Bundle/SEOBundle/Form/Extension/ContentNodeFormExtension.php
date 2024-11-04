<?php

namespace Oro\Bundle\SEOBundle\Form\Extension;

use Oro\Bundle\WebCatalogBundle\Form\Type\ContentNodeType;

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
