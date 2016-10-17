<?php

namespace Oro\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\EntityBundle\ORM\Repository\BatchIteratorInterface;
use Oro\Bundle\EntityBundle\ORM\Repository\BatchIteratorTrait;

class AccountGroupRepository extends EntityRepository implements BatchIteratorInterface
{
    use BatchIteratorTrait;

    /**
     * @param string $name
     *
     * @return null|AccountGroup
     */
    public function findOneByName($name)
    {
        return $this->findOneBy(['name' => $name]);
    }
}
