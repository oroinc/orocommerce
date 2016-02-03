<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\ChangedPriceListCollection;

class ChangedPriceListCollectionRepository extends EntityRepository
{
    /**
     * @return BufferedQueryResultIterator|ChangedPriceListCollection[]
     */
    public function getCollectionChangesIterator()
    {
        $qb = $this->createQueryBuilder('changes');

        return new BufferedQueryResultIterator($qb->getQuery());
    }

    /**
     * @param AccountGroup $accountGroup
     * @param integer[] $websiteIds
     * @param InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
     */
    public function insertAccountWebsitePairsByAccountGroup(
        AccountGroup $accountGroup,
        array $websiteIds,
        InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
    ) {
        $queryBuilder = $this->getEntityManager()
            ->getRepository('OroB2BPricingBundle:PriceListToAccount')
            ->getAccountWebsitePairsByAccountGroupQueryBuilder($accountGroup, $websiteIds);
        $insertFromSelectQueryExecutor->execute(
            $this->getClassName(),
            [
                'account_id',
                'website'
            ],
            $queryBuilder
        );
    }
}
