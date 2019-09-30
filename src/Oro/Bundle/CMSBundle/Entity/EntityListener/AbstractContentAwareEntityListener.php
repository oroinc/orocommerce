<?php

namespace Oro\Bundle\CMSBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\ContentWidgetUsage;
use Oro\Bundle\CMSBundle\Parser\TwigContentWidgetParser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Listens events of the required entity.
 */
abstract class AbstractContentAwareEntityListener
{
    /** @var TwigContentWidgetParser */
    private $twigParser;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var PropertyAccessor */
    private $propertyAccessor;

    /** @var array */
    protected $widgets = [];

    /**
     * @param TwigContentWidgetParser $twigParser
     * @param DoctrineHelper $doctrineHelper
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(
        TwigContentWidgetParser $twigParser,
        DoctrineHelper $doctrineHelper,
        PropertyAccessor $propertyAccessor
    ) {
        $this->twigParser = $twigParser;
        $this->doctrineHelper = $doctrineHelper;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param object $entity
     */
    public function postPersist($entity): void
    {
        if (!is_a($entity, $this->getSupportedEntityClass())) {
            return;
        }

        $content = (string) $this->propertyAccessor->getValue($entity, $this->getSupportedFieldName());

        $this->add($this->twigParser->parseNames($content), $entity);
    }

    /**
     * @param object $entity
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate($entity, PreUpdateEventArgs $args): void
    {
        if (!is_a($entity, $this->getSupportedEntityClass()) ||
            !$args->hasChangedField($this->getSupportedFieldName())
        ) {
            return;
        }

        $oldNames = $this->twigParser->parseNames($args->getOldValue($this->getSupportedFieldName()));
        $newNames = $this->twigParser->parseNames($args->getNewValue($this->getSupportedFieldName()));

        $this->remove(\array_diff($oldNames, $newNames), $entity);
        $this->add(\array_diff($newNames, $oldNames), $entity);
    }

    /**
     * @param object $entity
     */
    public function preRemove($entity): void
    {
        if (!is_a($entity, $this->getSupportedEntityClass())) {
            return;
        }

        $this->doctrineHelper->getEntityRepository(ContentWidgetUsage::class)
            ->remove($this->getSupportedEntityClass(), $this->doctrineHelper->getSingleEntityIdentifier($entity));
    }

    /**
     * @return string
     */
    abstract protected function getSupportedEntityClass(): string;

    /**
     * @return string
     */
    abstract protected function getSupportedFieldName(): string;

    /**
     * @param array $widgets
     * @param object $entity
     */
    private function add(array $widgets, $entity): void
    {
        $entityClass = $this->getSupportedEntityClass();
        $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);

        $repository = $this->doctrineHelper->getEntityRepository(ContentWidgetUsage::class);

        foreach ($widgets as $widget) {
            $widget = $this->getContentWidget($widget, $entity);
            if (!$widget instanceof ContentWidget) {
                continue;
            }

            $repository->add($entityClass, $entityId, $widget);
        }
    }

    /**
     * @param array $widgets
     * @param object $entity
     */
    private function remove(array $widgets, $entity): void
    {
        $entityClass = $this->getSupportedEntityClass();
        $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);

        $repository = $this->doctrineHelper->getEntityRepository(ContentWidgetUsage::class);

        foreach ($widgets as $widget) {
            $widget = $this->getContentWidget($widget, $entity);
            if (!$widget instanceof ContentWidget) {
                continue;
            }

            $repository->remove($entityClass, $entityId, $widget);
        }
    }

    /**
     * @param string $widget
     * @param object $entity
     * @return ContentWidget|null
     */
    protected function getContentWidget(string $widget, $entity): ?ContentWidget
    {
        if (!isset($this->widgets[$widget])) {
            $this->widgets[$widget] = $this->doctrineHelper->getEntityRepository(ContentWidget::class)
                ->findOneBy(['name' => $widget, 'organization' => $entity->getOrganization()]);
        }

        return $this->widgets[$widget];
    }
}
