<?php

namespace Oro\Bundle\CMSBundle\Entity\EntityListener;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
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

    /**
     * {@inheritdoc}
     */
    protected function getContentWidget(string $widget, $entity): ?ContentWidget
    {
        if (!isset($this->widgets[$widget])) {
            $this->widgets[$widget] = $this->doctrineHelper->getEntityRepository(ContentWidget::class)
                ->findOneBy(['name' => $widget, 'organization' => $entity->getContentBlock()->getOrganization()]);
        }

        return $this->widgets[$widget];
    }
}
