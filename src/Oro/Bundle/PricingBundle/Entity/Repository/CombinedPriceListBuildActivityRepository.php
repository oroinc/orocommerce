<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListBuildActivity;

/**
 * Doctrine Entity repository for CombinedPriceListBuildActivity entity
 */
class CombinedPriceListBuildActivityRepository extends EntityRepository
{
    public function deleteActivityRecordsForJob(int $jobId): void
    {
        $qb = $this->createQueryBuilder('r')->delete()
            ->where('r.parentJobId = :parentJobId')
            ->setParameter('parentJobId', $jobId, Types::INTEGER);

        $qb->getQuery()->execute();
    }

    public function deleteActivityRecordsForCombinedPriceList(CombinedPriceList $cpl): void
    {
        $qb = $this->createQueryBuilder('r')->delete()
            ->where('r.combinedPriceList = :combinedPriceList')
            ->setParameter('combinedPriceList', $cpl);

        $qb->getQuery()->execute();
    }

    public function addBuildActivities(iterable $cpls, int $jobId = null): void
    {
        if (!$cpls) {
            return;
        }

        $em = $this->getEntityManager();
        $records = [];
        foreach ($cpls as $cpl) {
            $record = new CombinedPriceListBuildActivity();
            $record->setParentJobId($jobId);
            $record->setCombinedPriceList($cpl);
            $em->persist($record);
            $records[] = $record;
        }
        $em->flush($records);
    }
}
