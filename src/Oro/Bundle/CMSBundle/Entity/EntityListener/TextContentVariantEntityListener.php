<?php

namespace Oro\Bundle\CMSBundle\Entity\EntityListener;

use Oro\Bundle\CMSBundle\Entity\TextContentVariant;

/**
 * Listens events of the TextContentVariant entity.
 */
class TextContentVariantEntityListener extends AbstractContentAwareEntityListener
{
    /**
     * {@inheritdoc}
     */
    protected function getSupportedEntityClass(): string
    {
        return TextContentVariant::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSupportedFieldName(): string
    {
        return 'content';
    }
}
