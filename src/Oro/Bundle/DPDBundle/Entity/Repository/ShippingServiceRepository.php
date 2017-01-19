<?php

namespace Oro\Bundle\DPDBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class ShippingServiceRepository extends EntityRepository
{
    public function getAllShippingServiceCodes()
    {
        return $this->createQueryBuilder('s', 's.code')->getQuery()->getResult();
    }

}