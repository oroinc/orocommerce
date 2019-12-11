<?php

namespace Oro\Bundle\SEOBundle\Form\Extension;

use Oro\Bundle\CMSBundle\Form\Type\PageType;

class PageFormExtension extends BaseMetaFormExtension
{
    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [PageType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.cms.page';
    }
}
