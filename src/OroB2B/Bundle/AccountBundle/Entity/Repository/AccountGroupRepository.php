<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;

class AccountGroupRepository extends EntityRepository
{
    /**
     * @return AccountGroup[]
     */
    public function getPartialAccountGroups()
    {
        return $this
            ->createQueryBuilder('accountGroup')
            ->select('partial accountGroup.{id}')
            ->getQuery()
            ->getResult();
    }
}
