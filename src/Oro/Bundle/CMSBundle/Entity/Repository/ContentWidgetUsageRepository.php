<?php

namespace Oro\Bundle\CMSBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CMSBundle\Entity\ContentWidgetUsage;

/**
 * Doctrine repository for ContentWidgetUsage entity
 */
class ContentWidgetUsageRepository extends EntityRepository
{
    /**
     * Finds content widget usages for a specific entity or entity field.
     *
     * @return ContentWidgetUsage[]
     */
    public function findForEntityField(string $entityClass, int $entityId, ?string $entityField = null): array
    {
        $qb = $this->createQueryBuilder('usage')
            ->select('usage', 'contentWidget')
            ->innerJoin('usage.contentWidget', 'contentWidget')
            ->where('usage.entityClass = :entityClass')
            ->andWhere('usage.entityId = :entityId')
            ->setParameter('entityClass', $entityClass)
            ->setParameter('entityId', $entityId);
        if ($entityField) {
            $qb
                ->andWhere('usage.entityField = :entityField')
                ->setParameter('entityField', $entityField);
        }

        return $qb->getQuery()->getResult();
    }
}
