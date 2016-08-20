<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;

class PriceRuleChangeTriggerRepository extends EntityRepository
{
    public function deleteAll()
    {
        $this->_em->createQueryBuilder()->delete($this->getEntityName(), 'rule')->getQuery()->execute();
    }

    /**
     * @return BufferedQueryResultIterator|PriceRuleChangeTrigger[]
     */
    public function getTriggersIterator()
    {
        //TODO: handle depends triggers
        $qb = $this->createQueryBuilder('trigger');

        return new BufferedQueryResultIterator($qb);
    }
}
