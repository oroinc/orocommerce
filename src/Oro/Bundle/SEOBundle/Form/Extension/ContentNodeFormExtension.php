<?php

namespace Oro\Bundle\SEOBundle\Form\Extension;

use Oro\Bundle\WebCatalogBundle\Form\Type\ContentNodeType;

class ContentNodeFormExtension extends BaseMetaFormExtension
{
    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [ContentNodeType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.webcatalog.contentnode';
    }
}
