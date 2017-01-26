<?php

namespace Oro\Bundle\CustomerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;

class CustomerGroupRepository extends EntityRepository
{
    /**
     * @param string $name
     *
     * @return null|CustomerGroup
     */
    public function findOneByName($name)
    {
        return $this->findOneBy(['name' => $name]);
    }
}
