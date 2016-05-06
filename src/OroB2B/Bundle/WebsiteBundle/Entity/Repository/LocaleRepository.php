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

    /**
     * @return mixed
     */
    public function findRootsWithChildren()
    {
        $locales = $this->createQueryBuilder('l')
            ->addSelect('children')
            ->leftJoin('l.childLocales', 'children')
            ->getQuery()->execute();
        return array_filter($locales, function (Locale $locale) {
            return !$locale->getParentLocale();
        });
    }
}
