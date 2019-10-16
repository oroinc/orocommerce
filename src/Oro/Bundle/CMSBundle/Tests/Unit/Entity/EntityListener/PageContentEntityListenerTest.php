<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity\EntityListener;

use Oro\Bundle\CMSBundle\Entity\EntityListener\PageContentEntityListener;
use Oro\Bundle\CMSBundle\Entity\Page;

class PageContentEntityListenerTest extends ContentAwareEntityListenerTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getEntityListenerClass(): string
    {
        return PageContentEntityListener::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClass(): string
    {
        return Page::class;
    }
}
