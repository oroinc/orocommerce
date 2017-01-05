<?php

namespace Oro\Bundle\CustomerBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\EntityBundle\ORM\Repository\BatchIteratorInterface;
use Oro\Bundle\EntityBundle\ORM\Repository\BatchIteratorTrait;

// todo: check if BatchIterator required BB-4506
class CustomerGroupRepository extends EntityRepository implements BatchIteratorInterface
{
    use BatchIteratorTrait;

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
