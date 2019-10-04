<?php

namespace Oro\Bundle\CMSBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\ContentWidgetUsage;

/**
 * Doctrine repository for ContentWidgetUsage entity
 */
class ContentWidgetUsageRepository extends EntityRepository
{
    /**
     * @param ContentWidget $widget
     * @param string $entityClass
     * @param int $entityId
     */
    public function add(string $entityClass, int $entityId, ContentWidget $widget): void
    {
        $usage = $this->findOneBy(['contentWidget' => $widget, 'entityClass' => $entityClass, 'entityId' => $entityId]);
        if ($usage) {
            return;
        }

        $usage = new ContentWidgetUsage();
        $usage->setContentWidget($widget)
            ->setEntityClass($entityClass)
            ->setEntityId($entityId);

        $this->_em->persist($usage);
        $this->_em->flush($usage);
    }

    /**
     * @param string $entityClass
     * @param int $entityId
     * @param ContentWidget|null $widget
     */
    public function remove(string $entityClass, int $entityId, ?ContentWidget $widget = null): void
    {
        $qb = $this->createQueryBuilder('entity');
        $qb->delete()
            ->where($qb->expr()->eq('entity.entityClass', ':entityClass'))
            ->andWhere($qb->expr()->eq('entity.entityId', ':entityId'))
            ->setParameter('entityClass', $entityClass)
            ->setParameter('entityId', $entityId);

        if ($widget) {
            $qb->andWhere($qb->expr()->eq('entity.contentWidget', ':contentWidget'))
                ->setParameter('contentWidget', $widget);
        }

        $qb->getQuery()
            ->execute();
    }
}
