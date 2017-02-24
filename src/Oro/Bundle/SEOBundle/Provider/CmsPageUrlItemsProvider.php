<?php

namespace Oro\Bundle\SEOBundle\Provider;

use Oro\Bundle\CMSBundle\Entity\Page;

class CmsPageUrlItemsProvider extends AbstractUrlItemsProvider
{
    /**
     * {@inheritdoc}
     */
    protected function getType()
    {
        return 'cmsPage';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClass()
    {
        return Page::class;
    }
}
