<?php

namespace OroB2B\Bundle\WebsiteBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class LocaleRepository extends EntityRepository
{
    /**
     * @return array
     */
    public function getLocaleCodes()
    {
        $qb = $this->createQueryBuilder('locale');

        return $qb
            ->select('locale.code')
            ->getQuery()
            ->getScalarResult();
    }
}
