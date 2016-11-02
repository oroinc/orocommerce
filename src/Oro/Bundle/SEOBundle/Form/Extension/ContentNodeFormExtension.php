<?php

namespace Oro\Bundle\SEOBundle\Form\Extension;

use Oro\Bundle\WebCatalogBundle\Form\Type\ContentNodeType;

class ContentNodeFormExtension extends BaseMetaFormExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ContentNodeType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.webcatalog.contentnode';
    }
}
