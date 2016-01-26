<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

use OroB2B\Bundle\PricingBundle\Entity\ChangedPriceListCollection;

class ChangedPriceListCollectionRepository extends EntityRepository
{
    /**
     * @return BufferedQueryResultIterator|ChangedPriceListCollection[]
     */
    public function getCollectionChangesIterator()
    {
        $qb = $this->createQueryBuilder('changes');
        $qb->addOrderBy('changes.website')
            ->addOrderBy('changes.accountGroup')
            ->addOrderBy('changes.account');

        return new BufferedQueryResultIterator($qb->getQuery());
    }
}
