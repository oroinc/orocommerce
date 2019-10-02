<?php

namespace Oro\Bundle\CMSBundle\Entity\EntityListener;

use Oro\Bundle\CMSBundle\Entity\Page;

/**
 * Listens events of the Page entity.
 */
class PageContentEntityListener extends AbstractContentAwareEntityListener
{
    /**
     * {@inheritdoc}
     */
    protected function getSupportedEntityClass(): string
    {
        return Page::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSupportedFieldName(): string
    {
        return 'content';
    }
}
