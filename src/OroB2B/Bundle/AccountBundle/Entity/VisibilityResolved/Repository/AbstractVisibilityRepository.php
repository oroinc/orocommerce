<?php

namespace OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

abstract class AbstractVisibilityRepository extends EntityRepository
{
    /**
     * @param Website $website
     * @return int
     */
    public function clearTable(Website $website = null)
    {
        $qb = $this->createQueryBuilder('visibility_resolved')
            ->delete();

        if ($website) {
            $qb->andWhere('visibility_resolved.website = :website')
                ->setParameter('website', $website);
        }

        return $qb->getQuery()
            ->execute();
    }

    /**
     * @param InsertFromSelectQueryExecutor $insertFromSelect
     * @param integer $cacheVisibility
     * @param integer[] $categories
     * @param integer $accountId
     * @param integer|null $websiteId
     */
    abstract public function insertByCategory(
        InsertFromSelectQueryExecutor $insertFromSelect,
        $cacheVisibility,
        $categories,
        $accountId,
        $websiteId = null
    );

    /**
     * @param InsertFromSelectQueryExecutor $insertFromSelect
     * @param integer|null $websiteId
     */
    abstract public function insertStatic(InsertFromSelectQueryExecutor $insertFromSelect, $websiteId = null);
}
