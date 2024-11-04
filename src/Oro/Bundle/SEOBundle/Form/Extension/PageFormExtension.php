<?php

namespace Oro\Bundle\SEOBundle\Form\Extension;

use Oro\Bundle\CMSBundle\Form\Type\PageType;

class PageFormExtension extends BaseMetaFormExtension
{
    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [PageType::class];
    }

    #[\Override]
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.cms.page';
    }
}
