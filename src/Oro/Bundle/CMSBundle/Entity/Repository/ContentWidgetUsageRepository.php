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
     * @param string $entityClass
     * @param int $entityId
     * @param string|null $entityField
     *
     * @return ContentWidgetUsage[]
     */
    public function findForEntityField(
        string $entityClass,
        int $entityId,
        ?string $entityField = null
    ): array {
        $qb = $this->createQueryBuilder('usage');

        $qb
            ->select('usage', 'contentWidget')
            ->innerJoin('usage.contentWidget', 'contentWidget')
            ->where(
                $qb->expr()->eq('usage.entityClass', ':entityClass'),
                $qb->expr()->eq('usage.entityId', ':entityId')
            )
            ->setParameters([
                ':entityClass' => $entityClass,
                ':entityId' => $entityId,
            ]);

        if ($entityField) {
            $qb
                ->andWhere(
                    $qb->expr()->eq('usage.entityField', ':entityField')
                )
                ->setParameter(':entityField', $entityField);
        }

        return $qb->getQuery()->getResult();
    }
}
