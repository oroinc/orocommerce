<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class PriceRuleChangeTriggerRepository extends EntityRepository
{
    public function deleteAll()
    {
        $this->_em->createQueryBuilder()->delete($this->getEntityName(), 'rule')->getQuery()->execute();
    }
}
