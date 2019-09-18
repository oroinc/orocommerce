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
    private $doctrineHelper;

    /** @var PropertyAccessor */
    private $propertyAccessor;

    /** @var array */
    private $widgets = [];

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

        $this->add(
            $this->twigParser->parseNames($content),
            $this->getSupportedEntityClass(),
            $this->doctrineHelper->getSingleEntityIdentifier($entity)
        );
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

        $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);

        $this->remove(\array_diff($oldNames, $newNames), $this->getSupportedEntityClass(), $entityId);
        $this->add(\array_diff($newNames, $oldNames), $this->getSupportedEntityClass(), $entityId);
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
     * @param string $class
     * @param int $id
     */
    private function add(array $widgets, string $class, int $id): void
    {
        $repository = $this->doctrineHelper->getEntityRepository(ContentWidgetUsage::class);

        foreach ($widgets as $widget) {
            $widget = $this->getContentWidget($widget);
            if (!$widget instanceof ContentWidget) {
                continue;
            }

            $repository->add($class, $id, $widget);
        }
    }

    /**
     * @param array $widgets
     * @param string $class
     * @param int $id
     */
    private function remove(array $widgets, string $class, int $id): void
    {
        $repository = $this->doctrineHelper->getEntityRepository(ContentWidgetUsage::class);

        foreach ($widgets as $widget) {
            $widget = $this->getContentWidget($widget);
            if (!$widget instanceof ContentWidget) {
                continue;
            }

            $repository->remove($class, $id, $widget);
        }
    }

    /**
     * @param string $widget
     * @return ContentWidget|null
     */
    private function getContentWidget(string $widget): ?ContentWidget
    {
        if (!isset($this->widgets[$widget])) {
            $this->widgets[$widget] = $this->doctrineHelper->getEntityRepository(ContentWidget::class)
                ->findOneBy(['name' => $widget]);
        }

        return $this->widgets[$widget];
    }
}
