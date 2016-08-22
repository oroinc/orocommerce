<?php

namespace Oro\Bundle\SEOBundle\Form\Extension;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\CMSBundle\Form\Type\PageType;

use Doctrine\Common\Persistence\ManagerRegistry;

class PageFormExtension extends BaseMetaFormExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return PageType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetaFieldLabelPrefix()
    {
        return 'oro.cms.page';
    }
}
