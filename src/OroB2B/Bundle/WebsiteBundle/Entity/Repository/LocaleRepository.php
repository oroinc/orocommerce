<?php

namespace OroB2B\Bundle\WebsiteBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

/**
 * @method Locale|null findOneByCode($code)
 */
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
